<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrackingPageContractsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures();

        config()->set('services.pipeline.api_url', 'https://pipeline.example/api');
        config()->set('services.pipeline.base_url', 'https://pipeline.example');
        config()->set('services.pipeline.api_key', 'pipeline-key');
        config()->set('app.url', 'https://tracking.example');
    }

    public function test_tracking_page_renders_hydrated_props_for_a_found_brand_matched_shipment(): void
    {
        Http::preventStrayRequests();

        $company = $this->makeCompany([
            'pipeline_company_id' => 1001,
            'enable_map' => true,
            'enable_documents' => true,
            'requires_brand' => true,
            'brand' => 'ACME',
        ]);
        $this->makeCompanyApiToken($company, [
            'api_token' => 'company-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        $this->fakeTrackingApisFixture($company, 'BOL123');

        $this->get(route('trackShipment.index', [
            'trackingNumber' => 'BOL123',
            'searchOption' => 'bol',
            'brand' => 'ACME',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('brandedTracking/Index')
                ->where('initialTrackingData.bolNum', 'BOL123')
                ->where('initialCompany.name', $company->name)
                ->where('initialCompany.enable_map', true)
                ->where('initialCompany.enable_documents', true)
                ->has('initialShipmentCoordinates', 1)
                ->has('initialShipmentDocuments', 2)
                ->where('initialShipmentDocuments.0.name', 'bol'));
    }
}
