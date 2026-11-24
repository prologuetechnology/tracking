<?php

namespace Tests\Browser;

use App\Actions\Themes\CreateTheme;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DesignDraftWorkflowTest extends DuskTestCase
{
    public function test_staff_can_save_reopen_and_preview_a_company_draft_without_changing_public_tracking(): void
    {
        $this->seedCoreFixtures();
        $user = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['name' => 'Test Freight', 'pipeline_company_id' => 1001]);
        $theme = (new CreateTheme)->execute([
            'name' => 'Review palette',
            'style' => [
                'colors' => ['brand' => '#185E86', 'accent' => '#AADFF2', 'paper' => '#FFFFFF'],
                'corners' => 'round', 'headline' => 'serif', 'overrides' => [],
            ],
        ]);
        $original = $company->theme->colors['root']['primary'];
        $this->browse(function (Browser $browser) use ($user, $company, $theme, $original) {
            $browser->loginAs($user)->visit('/admin/companies?workspace=studio')
                ->waitFor('#find-company')->type('#find-company', 'absent fixture')->waitForText('No matching companies.')
                ->type('#find-company', '1001')->waitForLink('Test Freight', 5)
                ->click('a.signal-company-row')->waitForLink('Create design draft')
                ->visit('/admin/companies/'.$company->uuid.'?trackingNumber=BOL123&searchOption=bol')
                ->waitForLink('Design drafts')->clickLink('Design drafts')
                ->waitForLink('Create design draft')->clickLink('Create design draft')
                ->waitFor('#draft-name')->assertInputValue('#tracking-number', 'BOL123')->type('#draft-name', 'Autumn proposal')
                ->click('@inspector-appearance')->click('#draft-theme')->waitForText('Review palette')->click('@draft-theme-option-'.$theme->id)
                ->assertDontSeeIn('@draft-style-applied', 'Review palette')->press('Edit library version')
                ->waitFor('@theme-form')->select('#corners', 'soft')->press('Save new version')
                ->waitForText('Review palette v2 saved to the library.')
                ->assertDontSeeIn('@draft-style-applied', 'Review palette');
            $this->assertSame(0, $company->drafts()->count());
            $browser->press('Use in draft')->assertSeeIn('@draft-style-applied', 'Review palette v2')
                ->type('#tracking-number', 'BOL123')->press('Save draft')
                ->waitForLink('Preview saved revision 1')->clickLink('Test Freight drafts')
                ->waitForLink('Autumn proposal')->clickLink('Autumn proposal')
                ->waitFor('#draft-name')->assertInputValue('#draft-name', 'Autumn proposal')
                ->assertInputValue('#tracking-number', 'BOL123')
                ->click('@inspector-appearance')->assertSeeIn('#draft-theme', 'Review palette')
                ->assertSeeIn('#draft-theme-version', 'v2')
                ->clickLink('Preview saved revision 1')->waitFor('iframe')
                ->withinFrame('iframe', function (Browser $frame) use ($theme) {
                    $frame->waitForText('Shipment Details')->assertSee('BOL123')->assertDontSee('Copy Link')
                        ->assertScript('getComputedStyle(document.querySelector("h3")).fontFamily.includes("serif") && !getComputedStyle(document.querySelector("h3")).fontFamily.includes("sans-serif")', true)
                        ->assertScript('document.documentElement.style.getPropertyValue("--primary")', $theme->colors['root']['primary']);
                })
                ->press('Mobile')->waitUntil('Math.round(document.querySelector("iframe").getBoundingClientRect().width) === 390')
                ->type('#preview-number', 'BOL404')->press('Load shipment')
                ->withinFrame('iframe', fn (Browser $frame) => $frame->waitForText('Shipment unavailable'))
                ->clickLink('Back to draft')->waitFor('#draft-name')
                ->click('#studio-shipment-lookup > summary')->type('#tracking-number', 'BOL456')->press('Save new revision')
                ->waitForLink('Preview saved revision 2')->assertSeeLink('Preview revision 1')
                ->visit('/trackShipment?trackingNumber=BOL123&searchOption=bol')
                ->waitForText('Shipment Details')->assertSee('Copy Link')
                ->assertScript('document.documentElement.style.getPropertyValue("--primary")', $original);
        });
    }
}
