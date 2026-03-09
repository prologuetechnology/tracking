<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrackingAndCompanyApiTokenCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures();
        config()->set('services.pipeline.api_url', 'https://pipeline.example/api');
        config()->set('services.pipeline.base_url', 'https://pipeline.example');
        config()->set('services.pipeline.api_key', 'pipeline-key');
    }

    public function test_tracking_page_boundaries_and_not_found_page_render(): void
    {
        $this->get(route('trackShipment.notFound', 'BOL404'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('brandedTracking/Error')
                ->where('trackingNumber', 'BOL404'));

        $this->get(route('admin.tracking.index'))
            ->assertRedirect(route('login'));
    }

    public function test_tracking_api_requires_auth_and_can_return_coordinates_when_enabled(): void
    {
        Http::preventStrayRequests();

        $company = $this->makeCompany([
            'pipeline_company_id' => 1001,
            'enable_map' => true,
            'enable_documents' => true,
        ]);
        $this->makeCompanyApiToken($company, [
            'api_token' => 'company-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);
        $this->fakeTrackingApisFixture($company, 'BOL123');

        $this->postJson(route('api.shipmentTracking'), [
            'trackingNumber' => 'BOL123',
            'searchOption' => 'bol',
        ])->assertUnauthorized();

        $this->actingAs($this->makeStandardUser(['email' => 'tracking-surface@test.local']))
            ->postJson(route('api.shipmentCoordinates'), [
                'trackingNumber' => 'BOL123',
                'pipelineCompanyId' => (string) $company->pipeline_company_id,
            ])
            ->assertOk()
            ->assertJsonPath('shipmentCoordinates.0.lastLocation.coordinates.lat', 41.88);
    }

    public function test_company_api_token_delete_is_permission_gated(): void
    {
        $company = $this->makeCompany();
        $token = $this->makeCompanyApiToken($company);

        $this->deleteJson(route('api.admin.companyApiTokens.destroy', $token))
            ->assertUnauthorized();

        $this->actingAs($this->makeStandardUser(['email' => 'token-denied@test.local']))
            ->deleteJson(route('api.admin.companyApiTokens.destroy', $token))
            ->assertForbidden();

        $updater = $this->makeUserWithPermission('company:update');

        $this->actingAs($updater)
            ->deleteJson(route('api.admin.companyApiTokens.destroy', $token))
            ->assertNoContent();
    }
}
