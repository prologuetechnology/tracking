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
            $browser->resize(1440, 900)->loginAs($user)->visit('/admin/tracking')
                ->waitForText('Shipment Lookup')->assertVisible('#shipment-brand');
            $bounds = fn () => $browser->script('return ["searchOption","tracking-number-input","shipment-brand"].map(id => { const r = document.getElementById(id).getBoundingClientRect(); return [r.top + scrollY, r.bottom + scrollY] })')[0];
            foreach ([1440, 820] as $width) {
                $browser->resize($width, 900);
                $edges = $bounds();
                $this->assertEquals($edges[0], $edges[1]);
                $this->assertEquals($edges[0], $edges[2]);
            }
            $browser->click('[aria-label="About brand identifiers"]')->waitFor('[role="dialog"]')
                ->assertSeeIn('[role="dialog"]', 'Pipeline company ID');
            $this->assertSame($edges, $bounds());
            $browser->keys('[role="dialog"]', '{escape}')->waitUntilMissing('[role="dialog"]')
                ->type('@tracking-number-input', 'BOL123')
                ->click('@tracking-search-option-trigger')
                ->waitFor('@tracking-search-option-bol')
                ->click('@tracking-search-option-bol')
                ->click('@tracking-search-submit')
                ->waitForText('Shipment Details')
                ->waitForText('Shipment Documents')
                ->assertPresent('#trackingMap')
                ->assertSeeIn('.scan-inspector', 'Delivered')
                ->assertSeeLink('Customer tracking page')
                ->assertDontSeeLink('Company workspace')
                ->assertVisible('#shipment-brand');
            $this->assertSame($edges, $bounds());
            $browser->press('Clear')->waitUntilMissing('.scan-console')->resize(390, 844);
            $mobile = $bounds();
            $this->assertGreaterThan($mobile[0][1], $mobile[1][0]);
            $this->assertGreaterThan($mobile[1][1], $mobile[2][0]);
            $browser->assertScript('document.documentElement.scrollWidth === innerWidth')->resize(1440, 900);
        });
    }

    public function test_scan_and_stop_selection_preserve_repeated_visits_and_untimed_events(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['pipeline_company_id' => 1001]);
        $this->browse(function (Browser $browser) use ($staff, $company) {
            $browser->loginAs($staff)->visit('/admin/tracking')
                ->type('@tracking-number-input', 'SIGNAL-SCANS')->click('@tracking-search-submit')
                ->waitFor('.scan-inspector')->assertSeeIn('.scan-inspector', 'Delivered')
                ->assertPresent('button[aria-label="Stop 1, Chicago, IL"]')
                ->click('button[aria-label="Stop 2, Memphis, TN"]')
                ->assertSeeIn('.scan-inspector', 'Delivery exception')
                ->press('Exceptions')->assertSeeIn('.scan-inspector', 'Delivery exception')
                ->click('button[aria-label="Stop 3, Chicago, IL"]')
                ->assertSeeIn('.scan-inspector', 'Delivered')
                ->click('.scan-list li:last-child button')->assertSeeIn('.scan-inspector', 'Time unavailable')
                ->assertSeeIn('.scan-inspector', 'Location unavailable')
                ->assertSeeLink('Company workspace')->assertSeeLink('Create design draft');
            $draft = $browser->attribute('a[href*="/design-drafts/create"]', 'href');
            $this->assertSame(route('admin.drafts.create', $company->uuid), strtok($draft, '?'));
            parse_str(parse_url($draft, PHP_URL_QUERY), $parameters);
            $this->assertEquals(['trackingNumber' => 'SIGNAL-SCANS', 'searchOption' => 'carrierPro'], $parameters);
            $browser->type('@tracking-number-input', 'SIGNAL-EMPTY')->click('@tracking-search-submit')
                ->waitForText('No carrier scans reported yet.')->assertSeeIn('.scan-inspector', 'No scan selected.')
                ->type('@tracking-number-input', 'BOL404')->click('@tracking-search-submit')
                ->waitForText('Shipment not found.')->assertMissing('.scan-console');
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
                ->waitForText('Shipment Documents')->assertSee('BOL123')
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
                ->waitForText('Shipment not found.')->assertDontSee('Shipment Details');
        });
    }
}
