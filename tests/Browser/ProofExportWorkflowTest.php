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
                    ->type('#draft-name', 'Receiving desk')->click('#draft-layout')->click('@draft-layout-option-answer.v1')
                    ->click('#draft-emphasis')->click('@draft-emphasis-option-event')
                    ->type('#tracking-number', 'BOL123')->press('Save draft')
                    ->waitForLink('Preview saved revision 1')->clickLink('Preview saved revision 1')
                    ->waitForText('Export desktop and mobile PNGs')->press('Export desktop and mobile PNGs')
                    ->waitForLink('Desktop PNG', 120)->assertSeeLink('Mobile PNG')
                    ->press('Record customer approval')->type('input[id^="approver-"]', 'Fixture reviewer')
                    ->press('Save customer approval')->waitForText('Publish approved revision')
                    ->press('Publish approved revision')->acceptDialog()->waitForText('Live design');

                $proof = DesignProof::sole();
                foreach (['desktop' => 1440, 'mobile' => 390] as $view => $width) {
                    $file = Storage::disk('local')->path($proof->directory.'/'.$view.'.png');
                    $this->assertSame($width, getimagesize($file)[0]);
                    $this->assertSame(IMAGETYPE_PNG, getimagesize($file)[2]);
                    $this->assertSame($proof->checksums[$view], hash_file('sha256', $file));
                }
                foreach ([1440, 390] as $width) {
                    $browser->resize($width, 900)->visit($public)->waitForText('Carrier updates')
                        ->assertSeeIn('h1', 'Delivered')->assertSee('BOL123')
                        ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth');
                }
                $browser->resize(1440, 900)->visit($drafts)->waitForText('Live tracking design')
                    ->click('summary')->press('Restore original Classic branding')->acceptDialog()
                    ->waitUntilMissingText('Restore original Classic branding')
                    ->visit($public)->waitForText('Shipment Details')->assertSee('Status History');
                $draft = $company->drafts()->where('name', 'Receiving desk')->sole();
                $browser->visit(route('admin.drafts.preview', [$company->uuid, $draft->uuid, 1]))
                    ->waitForLink('Desktop PNG')->assertSeeLink('Mobile PNG');
            });
        } finally {
            foreach (DesignProof::whereNotNull('directory')->get() as $proof) {
                Storage::disk('local')->deleteDirectory(dirname($proof->directory));
            }
        }
    }
}
