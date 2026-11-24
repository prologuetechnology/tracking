<?php

namespace Tests\Browser;

use App\Actions\DesignDrafts\ChangeTrackingPublication;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Models\Company;
use App\Models\DesignProof;
use App\Support\Testing\FakePipelinePayloads;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DuskTestCase;

class CuratedLayoutScenariosTest extends DuskTestCase
{
    public static function viewports(): array
    {
        return ['desktop' => [1440, 900], 'mobile' => [390, 844]];
    }

    #[DataProvider('viewports')]
    public function test_public_brand_links_show_their_published_route_log_or_scan_register(int $width, int $height): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $this->publishForBrand('JOURNEY', 'journey.v1', 'pine', $staff->id);
        $this->publishForBrand('RECORD', 'record.v1', 'iris', $staff->id);

        $this->browse(function (Browser $browser) use ($width, $height) {
            $browser->resize($width, $height)
                ->visit($this->trackingLink('journey'))
                ->waitFor('[data-tracking-layout="journey"]')
                ->assertTitleContains('JOURNEY Freight')
                ->assertScript('document.querySelector("[data-tracking-footer]").offsetWidth === document.documentElement.clientWidth')
                ->assertScript('Math.abs(document.querySelector("[data-tracking-footer]").getBoundingClientRect().bottom + scrollY - document.documentElement.scrollHeight) < 3')
                ->assertSeeIn('#journey-title', 'Dallas')
                ->assertSeeIn('#journey-title', 'Chicago')
                ->assertPresent('#journey-title.font-sans')
                ->assertSee('Travel log')
                ->assertSee('Estimated delivery')
                ->assertSee('Not available')
                ->assertSee('4 events')
                ->assertSeeIn('[data-scan-index="0"]', 'In transit')
                ->assertSeeIn('[data-scan-index="0"]', 'Approx.')
                ->assertSee('Receiving address')
                ->assertDontSee('Delivery confirmed')
                ->assertScript('Array.from(document.querySelectorAll("h3 time"), day => day.dateTime).join(",")', '2026-03-06,2026-03-05,2026-03-04')
                ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth')
                ->press('Earlier exception')
                ->assertScript('document.activeElement.textContent.includes("Delivery exception")')
                ->click('main details summary')
                ->assertDontSee('Leave at dock door 3.')
                ->assertDontSee('PO-1')
                ->assertNotPresent('#trackingMap')
                ->assertDontSee('Shipment documents')
                ->assertDontSee('Shipment record');

            $button = 'button[aria-controls="scan-details-0"]';
            $browser->visit($this->trackingLink('record'))
                ->waitFor('[data-tracking-layout="record"]')
                ->assertTitleContains('RECORD Freight')
                ->assertScript('document.querySelector("[data-tracking-footer]").offsetWidth === document.documentElement.clientWidth')
                ->assertScript('Math.abs(document.querySelector("[data-tracking-footer]").getBoundingClientRect().bottom + scrollY - document.documentElement.scrollHeight) < 3')
                ->assertSeeIn('h1', 'Shipment record')
                ->assertSeeIn('#scan-register', 'Scan register')
                ->assertSeeIn('#shipment-file', 'Shipment file')
                ->assertSee('Not available')
                ->assertSee('4 events')
                ->assertSeeIn($button, 'In transit')
                ->assertAttribute($button, 'aria-expanded', 'false')
                ->click($button)
                ->waitFor('#scan-details-0')
                ->assertAttribute($button, 'aria-expanded', 'true')
                ->assertScript('document.querySelector("#scan-details-0").textContent.includes("Approximate scan time")')
                ->assertSeeIn('#scan-details-0', 'PRO-CARRIER-SCAN')
                ->assertSeeIn('#scan-details-0', 'Acme Carrier')
                ->click('button[aria-controls="scan-details-1"]')
                ->assertAttribute($button, 'aria-expanded', 'false')
                ->assertSeeIn('#scan-details-1', 'Earlier carrier exception')
                ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth')
                ->assertDontSee('Travel log')
                ->visit($this->trackingLink('missing'))
                ->waitForText('Shipment not found.')
                ->assertNotPresent('[data-tracking-layout]');
        });
    }

    #[DataProvider('viewports')]
    public function test_answer_details_and_journey_route_show_the_reported_scans_at_both_widths(int $width, int $height): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $this->publishForBrand('ANSWER', 'answer.v1', 'lake', $staff->id);
        $this->publishForBrand('ITINERARY', 'journey.v1', 'pine', $staff->id, showMap: true);

        $this->browse(function (Browser $browser) use ($width, $height) {
            $browser->resize($width, $height)->visit($this->trackingLink('answer'))
                ->waitFor('#answer-history')
                ->assertScript('document.querySelector("[data-tracking-footer]").offsetWidth === document.documentElement.clientWidth')
                ->assertScript('Math.abs(document.querySelector("[data-tracking-footer]").getBoundingClientRect().bottom + scrollY - document.documentElement.scrollHeight) < 3')
                ->assertSeeIn('#answer-history', 'Tracking history')
                ->assertSeeIn('[data-scan-index="0"]', 'Latest scan', ignoreCase: true)
                ->assertSeeIn('[data-scan-index="1"]', 'Earlier exception', ignoreCase: true)
                ->assertDontSee('Current exception', ignoreCase: true)
                ->assertSee('Not available')
                ->press('Shipment details')->waitFor('[role="dialog"]')
                ->assertSeeIn('[role="dialog"]', 'Origin Hub')
                ->click('[role="dialog"] details[data-section="references"] summary')
                ->assertSeeIn('[role="dialog"]', FakePipelinePayloads::MULTIDAY_REFERENCE)
                ->keys('[role="dialog"] summary', '{escape}')
                ->waitUntilMissing('[role="dialog"]')
                ->assertScript('document.activeElement.textContent.includes("Shipment details")')
                ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth')
                ->visit($this->trackingLink('itinerary'))
                ->waitFor('[data-featured][data-section="map"]')
                ->assertScript('Array.from(document.querySelectorAll(".route-stops li"), stop => stop.textContent.trim()).join("/")', 'Dallas, TX/Memphis, TN/Chicago, IL')
                ->assertNotPresent('#trackingMap')
                ->click('[data-featured] summary')->waitFor('#trackingMap')
                ->click('[data-featured] summary')->waitUntilMissing('#trackingMap')
                ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth');
        });
    }

    public function test_markerless_published_answer_retains_its_original_shipment_details_and_history(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $this->publishForBrand('ORIGINAL', 'answer.v1', 'lake', $staff->id, legacy: true);
        $this->browse(function (Browser $browser) {
            $browser->visit($this->trackingLink('original'))->waitFor('#answer-history')
                ->assertSeeIn('#answer-history', 'Carrier updates')
                ->assertSee('More shipment details')
                ->assertNotPresent('button[aria-haspopup="dialog"]');
        });
    }

    public function test_staff_reopens_the_exact_original_proof_from_publication_history(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $first = $this->publishForBrand('JOURNEY', 'journey.v1', 'pine', $staff->id);
        $brand = Company::findOrFail($first->revision->draft->company_id);
        $second = $first->replicate(['uuid']);
        $second->shipment_reference = 'REPLACEMENT-PROOF';
        $second->save();
        $historical = $brand->publications()->sole();
        $historical->update(['design_proof_id' => $first->id]);
        $next = $historical->replicate();
        $next->design_proof_id = $second->id;
        $next->save();
        $list = route('admin.drafts.index', $brand->uuid, false);
        $archive = route('admin.drafts.preview', [$brand->uuid, $first->revision->draft->uuid, 1], false);

        $this->browse(function (Browser $browser) use ($staff, $first, $second, $list, $archive) {
            $browser->resize(1440, 900)->loginAs($staff)->visit($list)
                ->waitForLink('View archived proof')->clickLink('View archived proof')
                ->waitFor('#proof-'.$second->uuid)
                ->assertPathIs($archive)
                ->assertFragmentIs('proof-'.$second->uuid)
                ->assertFocused('#proof-'.$second->uuid)
                ->assertSeeIn('#proof-'.$second->uuid, 'REPLACEMENT-PROOF')
                ->assertPresent('#proof-'.$second->uuid.' a[href$="/'.$second->uuid.'/desktop.png"]')
                ->assertPresent('#proof-'.$second->uuid.' a[href$="/'.$second->uuid.'/mobile.png"]')
                ->visit($list)->waitForText('Publication history')->press('Publication history')
                ->waitFor('a[href$="#proof-'.$first->uuid.'"]')->click('a[href$="#proof-'.$first->uuid.'"]')
                ->waitFor('#proof-'.$first->uuid)
                ->assertFragmentIs('proof-'.$first->uuid)
                ->assertFocused('#proof-'.$first->uuid)
                ->assertSeeIn('#proof-'.$first->uuid, FakePipelinePayloads::MULTIDAY_REFERENCE)
                ->assertPresent('#proof-'.$first->uuid.' a[href$="/'.$first->uuid.'/desktop.png"]')
                ->assertPresent('#proof-'.$first->uuid.' a[href$="/'.$first->uuid.'/mobile.png"]');
        });
    }

    private function trackingLink(string $brand): string
    {
        return route('trackShipment.index', [
            'searchOption' => 'bol',
            'trackingNumber' => FakePipelinePayloads::MULTIDAY_REFERENCE,
            'companyId' => 1001,
            'brand' => $brand,
        ], false);
    }

    private function publishForBrand(string $brand, string $layout, string $palette, int $staffId, bool $showMap = false, bool $legacy = false): DesignProof
    {
        $company = $this->makeCompany([
            'name' => $brand.' Freight',
            'enable_map' => $showMap,
            'brand' => $brand,
            'requires_brand' => true,
            'pipeline_company_id' => 1001,
        ]);
        $draft = app(SaveDesignDraft::class)->execute($company, [
            'name' => $brand.' public design',
            'layout' => $layout,
            'palette' => $palette,
            'headline_style' => 'sans',
            'section_orders' => [$layout => $showMap ? ['map', 'receiving', 'references', 'instructions', 'documents'] : ['receiving', 'map', 'references', 'instructions', 'documents']],
            'theme_id' => null,
            'logo_image_id' => null,
            'banner_image_id' => null,
            'footer_image_id' => null,
            'sections' => $showMap ? ['enable_map'] : [],
            'search_option' => 'bol',
        ], $staffId);
        $revision = $draft->revisions()->sole();
        if ($legacy) {
            $revision->update(['design' => collect($revision->design)->except('presentation_version')->all()]);
        }
        $proof = $revision->proofs()->create([
            'snapshot' => [],
            'shipment_reference' => FakePipelinePayloads::MULTIDAY_REFERENCE,
            'created_by' => $staffId,
        ]);
        $proof->forceFill(['status' => 'ready', 'approved_at' => now(), 'approved_by' => $staffId])->save();

        $revision->approve($staffId);
        app(ChangeTrackingPublication::class)->execute($company, $revision, 0, $staffId);

        return $proof;
    }
}
