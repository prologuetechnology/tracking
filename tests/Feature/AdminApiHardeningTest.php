<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyApiToken;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminApiHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config()->set('services.pipeline.api_url', 'https://pipeline.example/api');
        config()->set('services.pipeline.base_url', 'https://pipeline.example');
        config()->set('services.pipeline.api_key', 'pipeline-key');
    }

    public function test_company_api_token_store_returns_conflict_when_company_already_has_a_token(): void
    {
        Http::preventStrayRequests();

        $admin = $this->createSuperAdmin();
        $company = $this->createCompany(1001);
        CompanyApiToken::query()->create([
            'company_id' => $company->id,
            'api_token' => 'existing-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        $this->actingAs($admin)
            ->postJson(route('api.admin.companyApiTokens.store'), [
                'company_id' => $company->id,
                'api_token' => 'new-token',
                'trackingNumber' => 'BOL123',
            ])
            ->assertConflict()
            ->assertJsonPath('message', 'Company already has api token.');
    }

    public function test_company_api_token_store_rejects_invalid_pipeline_tokens(): void
    {
        Http::preventStrayRequests();

        $admin = $this->createSuperAdmin();
        $company = $this->createCompany(1001);

        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response([
                'data' => [
                    [
                        'companyId' => 9999,
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->postJson(route('api.admin.companyApiTokens.store'), [
                'company_id' => $company->id,
                'api_token' => 'invalid-token',
                'trackingNumber' => 'BOL123',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid API token.');

        $this->assertDatabaseMissing('company_api_tokens', [
            'company_id' => $company->id,
            'api_token' => 'invalid-token',
        ]);
    }

    public function test_company_api_token_store_persists_valid_tokens_with_a_flat_resource_shape(): void
    {
        Http::preventStrayRequests();

        $admin = $this->createSuperAdmin();
        $company = $this->createCompany(1001);

        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response([
                'data' => [
                    [
                        'companyId' => 1001,
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->postJson(route('api.admin.companyApiTokens.store'), [
                'company_id' => $company->id,
                'api_token' => 'valid-token',
                'trackingNumber' => 'BOL123',
            ])
            ->assertCreated()
            ->assertJsonPath('company_id', $company->id)
            ->assertJsonPath('api_token', 'valid-token')
            ->assertJsonPath('is_valid', true);

        $this->assertDatabaseHas('company_api_tokens', [
            'company_id' => $company->id,
            'api_token' => 'valid-token',
            'is_valid' => true,
        ]);
    }

    public function test_company_api_token_validate_endpoint_returns_the_normalized_token_resource(): void
    {
        Http::preventStrayRequests();

        $viewer = $this->createUserWithPermission('company:show');
        $company = $this->createCompany(2002);
        CompanyApiToken::query()->create([
            'company_id' => $company->id,
            'api_token' => 'revalidated-token',
            'bol' => 'BOL456',
            'is_valid' => false,
        ]);

        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response([
                'data' => [
                    [
                        'companyId' => 2002,
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($viewer)
            ->getJson(route('api.admin.companyApiTokens.validate', $company))
            ->assertOk()
            ->assertJsonPath('company_id', $company->id)
            ->assertJsonPath('api_token', 'revalidated-token')
            ->assertJsonPath('is_valid', true)
            ->assertJsonMissingPath('data');

        $this->assertDatabaseHas('company_api_tokens', [
            'company_id' => $company->id,
            'is_valid' => true,
        ]);
    }

    public function test_authorized_users_can_start_impersonation(): void
    {
        $originalUser = $this->createSuperAdmin();
        $targetUser = User::factory()->create(['email' => 'impersonated@example.com']);

        $this->actingAs($originalUser)
            ->post(route('api.admin.impersonate.start', $targetUser->id))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($targetUser);
        $this->assertSame($originalUser->id, session('impersonate_original_id'));
    }

    public function test_super_admin_targets_cannot_be_impersonated(): void
    {
        $originalUser = $this->createSuperAdmin('original-admin@example.com');
        $targetUser = $this->createSuperAdmin('target-admin@example.com');

        $this->actingAs($originalUser)
            ->post(route('api.admin.impersonate.start', $targetUser->id))
            ->assertForbidden();

        $this->assertAuthenticatedAs($originalUser);
    }

    public function test_stop_impersonation_restores_the_original_user_session(): void
    {
        $originalUser = $this->createSuperAdmin();
        $impersonatedUser = User::factory()->create(['email' => 'stop-impersonation@example.com']);

        $this->withSession([
            'impersonate_original_id' => $originalUser->id,
        ])->actingAs($impersonatedUser)
            ->post(route('api.impersonate.stop'))
            ->assertRedirect(route('admin.users.index'));

        $this->assertAuthenticatedAs($originalUser);
        $this->assertFalse(session()->has('impersonate_original_id'));
    }

    protected function createSuperAdmin(string $email = 'hardening-admin@example.com'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->syncRoles(['Super Admin']);

        return $user;
    }

    protected function createUserWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }

    protected function createCompany(int $pipelineCompanyId): Company
    {
        $theme = Theme::query()->create([
            'name' => 'Hardening Theme '.str()->uuid(),
            'colors' => [
                'root' => [
                    'background' => '0 0% 100%',
                    'foreground' => '240 10% 4%',
                    'primary' => '240 6% 10%',
                    'primaryForeground' => '0 0% 98%',
                    'secondary' => '240 5% 96%',
                    'secondaryForeground' => '240 6% 10%',
                    'accent' => '200 50% 50%',
                    'accentForeground' => '0 0% 98%',
                    'destructive' => '0 84% 60%',
                    'destructiveForeground' => '0 0% 98%',
                    'muted' => '240 5% 96%',
                    'mutedForeground' => '240 4% 46%',
                    'popover' => '0 0% 100%',
                    'popoverForeground' => '240 10% 4%',
                    'card' => '0 0% 100%',
                    'cardForeground' => '240 10% 4%',
                    'border' => '240 6% 90%',
                    'input' => '240 6% 90%',
                    'ring' => '240 10% 4%',
                ],
            ],
            'radius' => '0.5rem',
            'is_system' => true,
            'derive_from' => 'primary',
        ]);

        return Company::query()->create([
            'name' => 'Hardening Company '.$pipelineCompanyId,
            'website' => 'https://hardening.test',
            'phone' => '555-000-1111',
            'email' => 'dispatch@hardening.test',
            'pipeline_company_id' => $pipelineCompanyId,
            'theme_id' => $theme->id,
            'requires_brand' => false,
        ]);
    }
}
