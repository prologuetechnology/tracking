<?php

namespace Tests\Browser;

use App\Actions\DesignDrafts\SaveDesignDraft;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StudioPreviewComparisonTest extends DuskTestCase
{
    public function test_saved_draft_auto_loads_and_uses_one_capture_for_live_comparison_and_keyboard_inspection(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['pipeline_company_id' => 1001, 'enable_documents' => true]);
        $draft = app(SaveDesignDraft::class)->execute($company, [
            'name' => 'Regional receiving', 'layout' => 'journey.v1',
            'theme_id' => null, 'logo_image_id' => null, 'banner_image_id' => null, 'footer_image_id' => null,
            'sections' => ['enable_documents'], 'search_option' => 'bol', 'tracking_number' => 'BOL123',
        ], $staff->id);
        $this->browse(function (Browser $browser) use ($company, $staff, $draft) {
            $draftFrame = 'iframe[title="Unsaved shipment preview"]';
            $liveFrame = 'iframe[title="Published shipment preview"]';
            $browser->resize(1440, 1000)->loginAs($staff)->visit(route('admin.drafts.show', [$company->uuid, $draft->uuid], false))
                ->waitFor($draftFrame)->withinFrame($draftFrame, fn (Browser $preview) => $preview->waitFor('#journey-title'))
                ->assertSee('Original Classic')->click('@inspector-sections')
                ->waitForTextIn('[data-order-key="receiving"]', 'Featured')
                ->assertSeeIn('[data-order-key="documents"]', 'No data on this shipment')
                ->click('#section-visible-receiving')
                ->waitForTextIn('[data-order-key="references"]', 'Featured')
                ->withinFrame($draftFrame, fn (Browser $preview) => $preview
                    ->assertPresent('[data-section="references"]')->assertNotPresent('[data-section="receiving"]'))
                ->press('Live')->waitFor($liveFrame)
                ->withinFrame($liveFrame, fn (Browser $preview) => $preview->waitForText('Shipment Details')->assertSee('BOL123'))
                ->assertMissing('[aria-label="Inspect preview element"]')
                ->press('Draft')->waitFor($draftFrame)
                ->waitForTextIn('[data-order-key="references"]', 'Featured')
                ->click('@inspector-layout')->click('button[aria-label="Inspect preview element"]')
                ->withinFrame($draftFrame, fn (Browser $preview) => $preview
                    ->waitFor('[data-section="references"][tabindex="0"]')->keys('[data-section="references"]', '{enter}'))
                ->waitUntil('document.activeElement.id === "section-visible-references"')
                ->assertAttribute('[aria-label="Inspect preview element"]', 'aria-pressed', 'false');
            $this->assertSame(1, $draft->revisions()->count());
            $this->assertSame(0, $company->publications()->count());
        });
    }
}
