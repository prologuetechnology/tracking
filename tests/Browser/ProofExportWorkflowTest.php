<?php

namespace Tests\Browser;

use App\Actions\DesignDrafts\QueueDesignProof;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Actions\DesignDrafts\TrackingDesignPage;
use App\Jobs\RenderDesignProof;
use App\Models\DesignProof;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProofExportWorkflowTest extends DuskTestCase
{
    public function test_staff_can_approve_and_publish_a_saved_revision_from_the_company_list_of_drafts_without_an_export(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $company = $this->makeCompany();
        $draft = app(SaveDesignDraft::class)->execute($company, [
            'name' => 'Summer review', 'layout' => 'record.v1', 'theme_id' => null, 'logo_image_id' => null,
            'banner_image_id' => null, 'footer_image_id' => null, 'sections' => [], 'search_option' => 'bol',
        ], $staff->id);
        $group = '[role="group"][aria-label="Summer review, revision 1"]';
        $this->browse(function (Browser $browser) use ($staff, $company, $group) {
            $browser->loginAs($staff)->visit(route('admin.drafts.index', $company->uuid))
                ->waitFor($group)->with($group, fn (Browser $row) => $row->press('Approve revision'))
                ->waitForTextIn($group, 'Approved')->assertSee('Original Classic branding');
            $this->assertSame(0, $company->publications()->count());
            $browser->with($group, fn (Browser $row) => $row->press('Publish revision'))->waitFor('[role="alertdialog"]')
                ->with('[role="alertdialog"]', fn (Browser $dialog) => $dialog->press('Publish revision'))
                ->waitForTextIn($group, 'Live design');
        });
        $this->assertSame($draft->revisions()->sole()->id, $company->publications()->sole()->published_revision_id);
        $this->assertDatabaseCount('design_proofs', 0);
    }

    public function test_pending_proof_turns_into_two_downloadable_images_on_the_open_revision(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['pipeline_company_id' => 1001]);
        $this->makeCompanyApiToken($company);
        $draft = app(SaveDesignDraft::class)->execute($company, [
            'name' => 'Download review', 'layout' => 'answer.v1', 'theme_id' => null, 'logo_image_id' => null,
            'banner_image_id' => null, 'footer_image_id' => null, 'sections' => [], 'search_option' => 'bol',
        ], $staff->id);
        $params = [$company->uuid, $draft->uuid, 1];
        Queue::fake([RenderDesignProof::class]);
        $this->actingAs($staff);
        $revision = $draft->revisions()->sole();
        $snapshot = app(TrackingDesignPage::class)->preview($company, $revision, ['trackingNumber' => 'BOL123', 'searchOption' => 'bol']);
        $snapshot['props']['frozenAt'] = now()->toIso8601String();
        $snapshot['props']['app'] = ['name' => 'Tracking', 'env' => 'production', 'disks' => ['spaces' => ['bucket' => null, 'region' => null]]];
        $proof = $revision->proofs()->create(['created_by' => $staff->id, 'shipment_reference' => 'BOL123', 'snapshot' => $snapshot]);
        app(QueueDesignProof::class)->execute($proof);
        try {
            $this->browse(function (Browser $browser) use ($params, $staff, $proof) {
                $browser->loginAs($staff)->visit(route('admin.drafts.preview', $params))->waitForText('Download links will appear here.')
                    ->assertDontSeeLink('Desktop PNG');
                app()->call([Queue::pushed(RenderDesignProof::class)->sole(), 'handle']);
                $browser->waitForLink('Desktop PNG', 15)->assertSeeLink('Mobile PNG');
                foreach ($proof->fresh()->checksums as $view => $checksum) {
                    $href = $browser->attribute('[aria-label="Download proof images"] a[href$="/'.$view.'.png"]', 'href');
                    $response = $this->get($href)->assertDownload('revision-1-'.$proof->uuid.'-'.$view.'.png')
                        ->assertHeader('Content-Type', 'image/png');
                    $this->assertSame($checksum, hash('sha256', $response->streamedContent()));
                }
            });
        } finally {
            Storage::disk('local')->deleteDirectory(DesignProof::companyDirectory($company->id));
        }
    }

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
                    ->waitForLink('Desktop PNG', 120)->assertSeeLink('Mobile PNG');
                $archived = DesignProof::sole();
                foreach (['dark', 'light'] as $appearance) {
                    $browser->click('@user-menu-trigger')->waitFor('@staff-appearance-menu')->click('@staff-appearance-menu')->waitFor('@appearance-'.$appearance)->click('@appearance-'.$appearance)
                        ->waitUntil("document.documentElement.dataset.staffAppearance === '{$appearance}'")
                        ->withinFrame('iframe[title="Shipment preview"]', fn (Browser $frame) => $frame
                            ->waitFor('#answer-history')->assertSee('BOL123')
                            ->assertScript('!document.documentElement.hasAttribute("data-staff-appearance")'));
                    foreach ($archived->checksums as $view => $checksum) {
                        $this->assertSame($checksum, hash_file('sha256', Storage::disk('local')->path($archived->directory.'/'.$view.'.png')));
                    }
                }
                $browser->press('Approve revision')->waitForText('Approved')
                    ->press('Publish revision')->waitFor('[role="alertdialog"]')->press('Cancel')->waitUntilMissing('[role="alertdialog"]')
                    ->press('Publish revision')->waitFor('[role="alertdialog"]')
                    ->with('[role="alertdialog"]', fn (Browser $dialog) => $dialog->press('Publish revision'))->waitForText('Live design');

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
                    ->press('Publication history')->waitForText('Restore original Classic branding')->press('Restore original Classic branding')->waitFor('[role="alertdialog"]')->press('Restore design')
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
                    ->click('#studio-shipment-lookup > button')->waitFor('#tracking-number')->assertInputValue('#tracking-number', 'BOL123')
                    ->assertSeeLink('Preview saved revision 1')
                    ->assertVisible('header.signal-mast nav[aria-label="Staff workspace"]')
                    ->assertScript('document.querySelector("header.signal-mast").getBoundingClientRect().bottom <= document.querySelector("#studio-workspace").getBoundingClientRect().top + 1')
                    ->assertScript('document.querySelector("#studio-preview-title").getBoundingClientRect().right <= document.querySelector("#design-editor").getBoundingClientRect().left')
                    ->assertDontSeeIn('header.signal-mast nav', 'Application Settings')
                    ->resize(390, 844)->click('[aria-label="Open staff navigation"]')
                    ->waitFor('[role="dialog"] nav')->assertSeeIn('[role="dialog"] nav', 'Shipment Lookup')
                    ->waitUntil('document.querySelector("[role=dialog]").getAnimations().every(animation => animation.playState !== "running")')
                    ->click('[role="dialog"] nav a[aria-current="location"]')
                    ->waitFor('@company-tile-'.$company->uuid)->with('@company-tile-'.$company->uuid, fn (Browser $tile) => $tile->clickLink('Design Studio'))
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
