<?php

namespace Tests\Unit;

use App\Actions\Tracking\GetShipmentCoordinates;
use App\Actions\Tracking\GetShipmentDocuments;
use App\Actions\Tracking\ResolveTrackingCompany;
use App\Actions\Tracking\ResolveTrackingPayload;
use App\Actions\Tracking\SearchShipment;
use App\Services\Pipeline\PipelineApiShipmentCoordinates;
use App\Services\Pipeline\PipelineApiShipmentSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.pipeline.api_url', 'https://pipeline.example/api');
        config()->set('services.pipeline.base_url', 'https://pipeline.example');
        config()->set('app.url', 'https://tracking.example');
    }

    public function test_it_searches_and_resolves_a_full_tracking_payload(): void
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

        $payload = (new ResolveTrackingPayload(
            new SearchShipment(new PipelineApiShipmentSearch),
            new ResolveTrackingCompany,
            new GetShipmentCoordinates(new PipelineApiShipmentCoordinates),
            new GetShipmentDocuments,
        ))->execute('BOL123', 'bol');

        $this->assertTrue($payload['found']);
        $this->assertSame('BOL123', $payload['trackingData']['bolNum']);
        $this->assertSame($company->id, $payload['company']->id);
        $this->assertCount(1, $payload['shipmentCoordinates']);
        $this->assertCount(2, $payload['shipmentDocuments']);
    }

    public function test_it_marks_missing_or_brand_mismatched_tracking_payloads_as_not_found(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response(['data' => []], 200),
        ]);

        $missing = (new ResolveTrackingPayload(
            new SearchShipment(new PipelineApiShipmentSearch),
            new ResolveTrackingCompany,
            new GetShipmentCoordinates(new PipelineApiShipmentCoordinates),
            new GetShipmentDocuments,
        ))->execute('BOL404', 'bol');

        $company = $this->makeCompany([
            'pipeline_company_id' => 1001,
            'requires_brand' => true,
            'brand' => 'ACME',
        ]);
        $this->fakeTrackingApisFixture($company, 'BOL123');

        $brandMissing = (new ResolveTrackingPayload(
            new SearchShipment(new PipelineApiShipmentSearch),
            new ResolveTrackingCompany,
            new GetShipmentCoordinates(new PipelineApiShipmentCoordinates),
            new GetShipmentDocuments,
        ))->execute('BOL123', 'bol');

        $this->assertFalse($missing['found']);
        $this->assertFalse($brandMissing['found']);
    }
}
