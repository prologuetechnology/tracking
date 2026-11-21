<?php

namespace Tests\Browser;

use App\Models\DesignDraftRevision;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DesignStudioWorkflowTest extends DuskTestCase
{
    public function test_staff_can_preview_unsaved_changes_and_reopen_their_layout_settings(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['name' => 'Studio Freight', 'pipeline_company_id' => 1001, 'enable_map' => true]);
        $this->makeCompanyApiToken($company);

        $this->browse(function (Browser $browser) use ($staff, $company) {
            $frame = 'iframe[title="Unsaved shipment preview"]';
            $browser->resize(1440, 1000)->loginAs($staff)->visit('/admin/companies/'.$company->uuid.'/design-drafts/create')
                ->waitFor('#draft-name')->assertRadioSelected('draft-layout', 'classic.v1')
                ->assertSee('Classic keeps its original section order.')
                ->type('#draft-name', 'Receiving proposal')->type('#tracking-number', 'BOL123')
                ->click('@draft-layout-option-answer.v1')->waitFor('#draft-emphasis')
                ->click('button[aria-label="Move Shipment references up"]')
                ->assertSeeIn('li[data-order-key]:first-child', 'Shipment references')
                ->click('@draft-layout-option-record.v1')->assertRadioSelected('draft-layout', 'record.v1')
                ->assertSeeIn('li[data-order-key]:first-child', 'Shipment references')
                ->click('@draft-layout-option-journey.v1')
                ->assertSeeIn('li[data-order-key]:first-child', 'Tracking map')
                ->click('button[aria-label="Move Receiving address up"]')
                ->click('#feature-enable_map')->assertSeeIn('li[data-order-key="map"]', 'Hidden')
                ->click('@palette-pine')->click('#draft-headline')->click('@draft-headline-option-serif')
                ->press('Update preview')->waitFor($frame)
                ->withinFrame($frame, function (Browser $preview) {
                    $preview->waitFor('[data-tracking-layout="journey"]')
                        ->assertSeeIn('#journey-title', 'Chicago')
                        ->assertSeeIn('[data-section="receiving"]', '9 End Ave')
                        ->assertMissing('[data-section="map"]');
                })
                ->click('button[aria-label="Mobile preview"]')
                ->withinFrame($frame, fn (Browser $preview) => $preview->waitUntil('window.innerWidth < 500'))
                ->click('button[aria-label="Desktop preview"]')
                ->withinFrame($frame, fn (Browser $preview) => $preview->waitUntil('window.innerWidth >= 1024'));

            $this->assertSame(0, $company->drafts()->count());
            $this->assertSame(0, DesignDraftRevision::query()->count());

            $browser->press('Save draft')->waitForLink('Preview saved revision 1')
                ->clickLink('Studio Freight drafts')->waitForLink('Receiving proposal')->clickLink('Receiving proposal')
                ->waitFor('#draft-name')->resize(390, 844)
                ->waitFor('[aria-label="Studio view"]')->assertVisible('#design-editor')
                ->click('[aria-label="Studio view"] button:last-child')->waitFor('#studio-preview-title')
                ->assertMissing('#design-editor')->click('[aria-label="Studio view"] button:first-child')
                ->waitFor('#draft-name')->assertRadioSelected('draft-layout', 'journey.v1')
                ->assertRadioSelected('palette', 'pine')->assertSeeIn('#draft-headline', 'Editorial serif')
                ->assertSeeIn('li[data-order-key]:first-child', 'Receiving address')
                ->assertSeeIn('li[data-order-key="map"]', 'Hidden')
                ->click('@draft-layout-option-answer.v1')
                ->assertSeeIn('li[data-order-key]:first-child', 'Shipment references');

            $this->assertSame(1, $company->drafts()->sole()->revisions()->count());
            $this->assertTrue($company->fresh()->hasFeature('enable_map'));
            $browser->visit('/trackShipment?trackingNumber=BOL123&searchOption=bol')
                ->waitForText('Shipment Details')->assertSee('Status History')->assertSee('BOL123')->resize(1440, 1000);
        });
    }
}
