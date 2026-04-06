<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TrackingFlowsTest extends DuskTestCase
{
    public function test_admin_tracking_search_renders_shipment_details_documents_and_map(): void
    {
        $this->seedCoreFixtures();

        $company = $this->makeCompany([
            'pipeline_company_id' => 1001,
            'enable_map' => true,
            'enable_documents' => true,
        ]);
        $this->makeCompanyApiToken($company, [
            'api_token' => 'browser-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        $user = $this->makeStandardUser(['email' => 'tracking-browser@example.com']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/tracking')
                ->waitForText('Track a Shipment')
                ->type('@tracking-number-input', 'BOL123')
                ->click('@tracking-search-option-trigger')
                ->waitFor('@tracking-search-option-bol')
                ->click('@tracking-search-option-bol')
                ->click('@tracking-search-submit')
                ->waitForText('Shipment Details')
                ->waitForText('Shipment Documents')
                ->assertPresent('#trackingMap');
        });
    }

    public function test_public_tracking_success_and_not_found_paths_render_correctly(): void
    {
        $this->seedCoreFixtures();

        $company = $this->makeCompany([
            'pipeline_company_id' => 1001,
            'enable_map' => true,
            'enable_documents' => true,
        ]);
        $this->makeCompanyApiToken($company, [
            'api_token' => 'public-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/trackShipment?trackingNumber=BOL123&searchOption=bol')
                ->waitForText('Shipment Details')
                ->waitForText('Shipment Documents')
                ->visit('/trackShipment?trackingNumber=BOL404&searchOption=bol')
                ->waitForText('Shipment not found.');
        });
    }

    public function test_public_tracking_rejects_brand_required_companies_without_a_brand_query(): void
    {
        $this->seedCoreFixtures();

        $company = $this->makeCompany([
            'pipeline_company_id' => 1001,
            'enable_map' => true,
            'enable_documents' => true,
            'requires_brand' => true,
            'brand' => 'ACME',
        ]);
        $this->makeCompanyApiToken($company, [
            'api_token' => 'brand-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/trackShipment?trackingNumber=BOL123&searchOption=bol')
                ->waitForText('Shipment not found.');
        });
    }
}
