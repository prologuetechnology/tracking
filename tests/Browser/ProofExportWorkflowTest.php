<?php

namespace Tests\Browser;

use App\Models\DesignProof;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProofExportWorkflowTest extends DuskTestCase
{
    public function test_staff_exports_real_pngs_then_publishes_and_restores_the_existing_tracking_link(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['name' => 'Northline Freight', 'pipeline_company_id' => 1001, 'enable_map' => true]);
        $this->makeCompanyApiToken($company);
        $drafts = '/admin/companies/'.$company->uuid.'/design-drafts';
        $public = '/trackShipment?trackingNumber=BOL123&searchOption=bol';

        try {
            $this->browse(function (Browser $browser) use ($company, $staff, $drafts, $public) {
                $browser->loginAs($staff)->visit($drafts.'/create')->waitFor('#draft-name')
                    ->type('#draft-name', 'Receiving desk')->click('@draft-layout-option-answer.v1')
                    ->click('#draft-emphasis')->click('@draft-emphasis-option-event')
                    ->type('#tracking-number', 'BOL123')->press('Save draft')
                    ->waitForLink('Preview saved revision 1')->clickLink('Preview saved revision 1')
                    ->waitForText('Shipment captured')->withinFrame('iframe[title="Shipment preview"]', fn (Browser $frame) => $frame->waitFor('#answer-history')->assertSee('BOL123'))
                    ->press('Export desktop and mobile PNGs')
                    ->waitForLink('Desktop PNG', 120)->assertSeeLink('Mobile PNG')
                    ->press('Record customer approval')->type('input[id^="approver-"]', 'Fixture reviewer')
                    ->press('Save customer approval')->waitForText('Publish approved revision')
                    ->press('Publish approved revision')->acceptDialog()->waitForText('Live design');

                $proof = DesignProof::sole();
                foreach (['desktop' => 1440, 'mobile' => 390] as $view => $width) {
                    $file = Storage::disk('local')->path($proof->directory.'/'.$view.'.png');
                    $this->assertStringStartsWith(storage_path('framework/testing/dusk.local/private/'), $file);
                    $this->assertSame($width, getimagesize($file)[0]);
                    $this->assertSame(IMAGETYPE_PNG, getimagesize($file)[2]);
                    $this->assertSame($proof->checksums[$view], hash_file('sha256', $file));
                }
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 900)->visit($public)->waitFor('#answer-history')
                        ->assertSeeIn('h1', 'Delivered')->assertSee('BOL123')
                        ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth');
                }
                $browser->resize(1440, 900)->visit($drafts)->waitForText('Live tracking design')
                    ->click('summary')->press('Restore original Classic branding')->acceptDialog()
                    ->waitUntilMissingText('Restore original Classic branding')
                    ->visit($public)->waitForText('Shipment Details')->assertSee('Status History');
                $draft = $company->drafts()->where('name', 'Receiving desk')->sole();
                $browser->visit(route('admin.drafts.preview', [$company->uuid, $draft->uuid, 1]))
                    ->waitForLink('Desktop PNG')->assertSeeLink('Mobile PNG')
                    ->press('Review images')->waitFor('[role="dialog"]')
                    ->waitUntil('document.querySelector("[role=dialog] img")?.naturalWidth === 1440')
                    ->click('[role="dialog"] [aria-label="Archived image"] button:last-child')
                    ->waitUntil('document.querySelector("[role=dialog] img")?.naturalWidth === 390')
                    ->press('Actual size')->assertScript('document.querySelector("[role=dialog] img").classList.contains("max-w-none")')
                    ->keys('[role="dialog"]', '{escape}')
                    ->waitUntilMissing('[role="dialog"]')
                    ->press('Use revision as new draft')->waitFor('#fork-name')->type('#fork-name', 'Alternative receiving desk')
                    ->press('Create draft')->waitFor('#draft-name')->assertInputValue('#draft-name', 'Alternative receiving desk')
                    ->assertInputValue('#tracking-number', 'BOL123')
                    ->assertSeeLink('Preview saved revision 1')
                    ->assertVisible('header.signal-mast nav[aria-label="Staff workspace"]')
                    ->assertScript('document.querySelector("header.signal-mast").getBoundingClientRect().bottom <= document.querySelector("#studio-workspace").getBoundingClientRect().top + 1')
                    ->assertScript('document.querySelector("#studio-preview-title").getBoundingClientRect().right <= document.querySelector("#design-editor").getBoundingClientRect().left')
                    ->assertDontSeeIn('header.signal-mast nav', 'Access')
                    ->resize(390, 844)->click('[aria-label="Open staff navigation"]')
                    ->waitFor('[role="dialog"] nav')->assertSeeIn('[role="dialog"] nav', 'Shipment lookup')
                    ->waitUntil('document.querySelector("[role=dialog]").getAnimations().every(animation => animation.playState !== "running")')
                    ->click('[role="dialog"] nav a[aria-current="location"]')
                    ->waitForLink('Alternative receiving desk')->assertSee('Saved drafts')
                    ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth');
                $this->assertSame(1, $draft->revisions()->count());
                $this->assertSame(1, $draft->revisions()->sole()->proofs()->count());
            });
        } finally {
            foreach (DesignProof::whereNotNull('directory')->get() as $proof) {
                Storage::disk('local')->deleteDirectory(dirname($proof->directory));
            }
        }
    }
}
