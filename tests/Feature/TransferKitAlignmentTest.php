<?php

namespace Tests\Feature;

use App\Models\AllowedDomain;
use App\Models\Company;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransferKitAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_are_redirected_away_from_the_companies_admin_page(): void
    {
        $this->get(route('admin.companies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_companies_index_is_hydrated_through_the_page_controller(): void
    {
        $theme = $this->createTheme();
        $company = $this->createCompany($theme);
        $user = $this->createUserWithPermission('company:show');

        $this->actingAs($user)
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/companies/Index')
                ->has('initialCompanies', 1)
                ->where('initialCompanies.0.name', $company->name)
                ->where('initialCompanies.0.theme.name', $theme->name));
    }

    public function test_themes_index_is_hydrated_through_the_page_controller(): void
    {
        $theme = $this->createTheme('Cranberry');
        $user = $this->createUserWithPermission('theme:show');

        $this->actingAs($user)
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/themes/Index')
                ->has('initialThemes', 1)
                ->where('initialThemes.0.name', $theme->name));
    }

    public function test_super_admins_can_view_allowed_domains_with_initial_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        $allowedDomain = AllowedDomain::query()->create([
            'domain' => 'example.com',
            'description' => 'Example domain',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.allowed-domains.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/allowedDomains/Index')
                ->has('initialAllowedDomains', 1)
                ->where('initialAllowedDomains.0.domain', $allowedDomain->domain));
    }

    public function test_company_updates_are_forbidden_without_the_matching_permission(): void
    {
        $theme = $this->createTheme();
        $company = $this->createCompany($theme);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('api.companies.update', $company), [
                'name' => 'Updated Company',
                'website' => 'https://updated.example.com',
                'phone' => '555-111-2222',
                'email' => 'ops@example.com',
                'pipeline_company_id' => 999,
                'requires_brand' => false,
                'brand' => null,
            ])
            ->assertForbidden();
    }

    public function test_companies_are_updated_through_the_api_with_a_stable_response_shape(): void
    {
        $theme = $this->createTheme();
        $company = $this->createCompany($theme);
        $user = $this->createUserWithPermission('company:update');

        $this->actingAs($user)
            ->putJson(route('api.companies.update', $company), [
                'name' => 'Updated Company',
                'website' => 'https://updated.example.com',
                'phone' => '555-111-2222',
                'email' => 'ops@example.com',
                'pipeline_company_id' => $company->pipeline_company_id,
                'requires_brand' => false,
                'brand' => null,
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Company')
            ->assertJsonPath('theme.name', $theme->name);
    }

    protected function createUserWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }

    protected function createTheme(string $name = 'Default Theme'): Theme
    {
        return Theme::query()->create([
            'name' => $name,
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
    }

    protected function createCompany(Theme $theme): Company
    {
        return Company::query()->create([
            'name' => 'Acme Logistics',
            'website' => 'https://acme.test',
            'phone' => '555-000-1111',
            'email' => 'dispatch@acme.test',
            'pipeline_company_id' => 1001,
            'theme_id' => $theme->id,
            'enable_map' => false,
            'enable_documents' => false,
            'is_active' => true,
            'requires_brand' => false,
            'brand' => null,
        ]);
    }
}
