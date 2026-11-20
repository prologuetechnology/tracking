<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DesignDraftWorkflowTest extends DuskTestCase
{
    public function test_staff_can_save_reopen_and_preview_a_company_draft_without_changing_public_tracking(): void
    {
        $this->seedCoreFixtures();
        $user = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['name' => 'Test Freight', 'pipeline_company_id' => 1001]);
        $theme = $this->makeTheme(['name' => 'Review palette']);
        $original = $company->theme->colors['root']['primary'];
        $this->browse(function (Browser $browser) use ($user, $company, $theme, $original) {
            $browser->loginAs($user)->visit('/admin/companies/'.$company->uuid)
                ->waitForLink('Design drafts')->clickLink('Design drafts')
                ->waitForLink('Create design draft')->clickLink('Create design draft')
                ->waitFor('#draft-name')->type('#draft-name', 'Autumn proposal')
                ->click('#draft-theme')->waitForText('Review palette')->click('@draft-theme-option-'.$theme->id)
                ->type('#tracking-number', 'BOL123')->press('Save draft')
                ->waitForLink('Preview saved revision 1')->clickLink('Test Freight drafts')
                ->waitForLink('Autumn proposal')->clickLink('Autumn proposal')
                ->waitFor('#draft-name')->assertInputValue('#draft-name', 'Autumn proposal')
                ->assertInputValue('#tracking-number', 'BOL123')
                ->assertSeeIn('#draft-theme', 'Review palette')
                ->clickLink('Preview saved revision 1')->waitFor('iframe')
                ->withinFrame('iframe', function (Browser $frame) use ($theme) {
                    $frame->waitForText('Shipment Details')->assertSee('BOL123')->assertDontSee('Copy Link')
                        ->assertScript('document.documentElement.style.getPropertyValue("--primary")', $theme->colors['root']['primary']);
                })
                ->press('Mobile')->waitUntil('Math.round(document.querySelector("iframe").getBoundingClientRect().width) === 390')
                ->type('#preview-number', 'BOL404')->press('Load shipment')
                ->withinFrame('iframe', fn (Browser $frame) => $frame->waitForText('Shipment unavailable'))
                ->clickLink('Back to draft')->waitFor('#draft-name')
                ->type('#tracking-number', 'BOL456')->press('Save new revision')
                ->waitForLink('Preview saved revision 2')->assertSeeLink('Preview revision 1')
                ->visit('/trackShipment?trackingNumber=BOL123&searchOption=bol')
                ->waitForText('Shipment Details')->assertSee('Copy Link')
                ->assertScript('document.documentElement.style.getPropertyValue("--primary")', $original);
        });
    }
}
