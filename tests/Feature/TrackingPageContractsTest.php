<?php

namespace Tests\Feature;

use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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
                ->component('brandedTracking/Classic')
                ->where('initialTrackingData.bolNum', 'BOL123')
                ->where('initialCompany.name', $company->name)
                ->where('initialCompany.enable_map', true)
                ->where('initialCompany.enable_documents', true)
                ->has('initialShipmentCoordinates', 1)
                ->has('initialShipmentDocuments', 2)
                ->where('initialShipmentDocuments.0.name', 'bol'));
    }

    public function test_tracking_page_resolves_the_branded_company_when_pipeline_company_id_is_shared(): void
    {
        Http::preventStrayRequests();

        $legacyTheme = $this->makeTheme(['name' => 'Legacy Theme']);
        $targetTheme = $this->makeTheme(['name' => 'Target Theme']);
        $targetLogo = $this->makeImage(ImageTypeEnum::LOGO->value, [
            'name' => 'Target Logo',
            'file_path' => 'images/target-logo.png',
        ]);

        $this->makeCompany([
            'name' => 'Legacy Company',
            'pipeline_company_id' => 742,
            'theme_id' => $legacyTheme->id,
            'requires_brand' => true,
            'brand' => 'LEGACY',
        ]);
        $targetCompany = $this->makeCompany([
            'name' => 'Target Company',
            'pipeline_company_id' => 742,
            'theme_id' => $targetTheme->id,
            'logo_image_id' => $targetLogo->id,
            'enable_map' => true,
            'enable_documents' => false,
            'requires_brand' => true,
            'brand' => 'TARGET',
            'email' => 'target@example.test',
        ]);

        $this->fakeTrackingApisFixture($targetCompany, 'BOL742');

        $this->get('/trackShipment?trackingNumber=PRO-BOL742&searchOption=carrierPro&companyId=742&brand=target')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('brandedTracking/Classic')
                ->where('initialTrackingData.bolNum', 'BOL742')
                ->where('initialCompany.name', 'Target Company')
                ->where('initialCompany.theme.name', 'Target Theme')
                ->where('initialCompany.logo.file_path', 'images/target-logo.png'));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://pipeline.example/api/shipmentSearch'
            && $request['trackNum'] === 'PRO-BOL742'
            && $request['searchOption'] === 'carrierPro');
    }

    public function test_carrier_pro_email_link_infers_branding_from_the_shipment_alone(): void
    {
        Http::preventStrayRequests();
        $company = $this->makeCompany(['pipeline_company_id' => 742]);
        $this->fakeTrackingApisFixture($company, 'BOL742');

        $this->get('/trackShipment?searchOption=carrierPro&trackingNumber=PRO-BOL742')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('brandedTracking/Classic')
                ->where('initialTrackingData.carrierPro', 'PRO-BOL742')
                ->where('initialCompany.id', $company->id)
                ->where('initialCompany.theme.id', $company->theme_id));
    }

    public function test_an_existing_link_without_a_company_record_uses_classic_and_default_branding(): void
    {
        Http::preventStrayRequests();
        $this->fakeTrackingApisFixture(trackingNumber: 'BOL123');

        $this->get('/trackShipment?trackingNumber=BOL123&searchOption=bol')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('brandedTracking/Classic')
                ->where('initialCompany', null)
                ->where('initialTrackingData.bolNum', 'BOL123')
                ->where('initialShipmentCoordinates', null)
                ->has('initialShipmentDocuments', 0));
    }

    public function test_tracking_page_redirects_to_not_found_when_shared_pipeline_company_id_has_no_brand(): void
    {
        Http::preventStrayRequests();

        $firstCompany = $this->makeCompany([
            'name' => 'First Shared Company',
            'pipeline_company_id' => 742,
            'requires_brand' => true,
            'brand' => 'FIRST',
        ]);
        $this->makeCompany([
            'name' => 'Second Shared Company',
            'pipeline_company_id' => 742,
            'requires_brand' => true,
            'brand' => 'SECOND',
            'email' => 'second-shared@example.test',
        ]);

        $this->fakeTrackingApisFixture($firstCompany, 'BOL742');

        $this->get(route('trackShipment.index', [
            'trackingNumber' => 'BOL742',
            'searchOption' => 'bol',
        ]))
            ->assertRedirect(route('trackShipment.notFound', 'BOL742'));
    }

    public function test_tracking_status_refetch_preserves_brand_resolution_for_shared_pipeline_company_ids(): void
    {
        Http::preventStrayRequests();

        $this->makeCompany([
            'name' => 'Legacy Company',
            'pipeline_company_id' => 742,
            'requires_brand' => true,
            'brand' => 'LEGACY',
        ]);
        $targetCompany = $this->makeCompany([
            'name' => 'Target Company',
            'pipeline_company_id' => 742,
            'enable_map' => true,
            'enable_documents' => false,
            'requires_brand' => true,
            'brand' => 'TARGET',
            'email' => 'target-refetch@example.test',
        ]);

        $this->fakeTrackingApisFixture($targetCompany, 'BOL742');

        $this->actingAs($this->makeStandardUser(['email' => 'tracking-refetch@example.test']))
            ->postJson(route('api.shipmentTracking'), [
                'trackingNumber' => 'BOL742',
                'searchOption' => 'bol',
                'brand' => 'target',
            ])
            ->assertOk()
            ->assertJsonPath('trackingData.bolNum', 'BOL742')
            ->assertJsonPath('company.name', 'Target Company')
            ->assertJsonPath('company.brand', 'TARGET');
    }
}
