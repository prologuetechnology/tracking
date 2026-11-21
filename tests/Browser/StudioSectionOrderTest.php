<?php

namespace Tests\Browser;

use App\Actions\DesignDrafts\DesignCatalog;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StudioSectionOrderTest extends DuskTestCase
{
    public function test_dragged_and_keyboard_order_stays_scoped_to_one_layout_without_revealing_hidden_sections(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $company = $this->makeCompany(['enable_map' => true, 'enable_documents' => false]);
        $company->syncFeatures(['enable_map']);

        $this->browse(function (Browser $browser) use ($staff, $company) {
            $readOrder = 'Array.from(document.querySelectorAll("[data-order-key]"), row => row.dataset.orderKey).join(",")';
            $browser->resize(1440, 1000)->loginAs($staff)
                ->visit(route('admin.drafts.create', $company->uuid, false))
                ->waitFor('#draft-name')->type('#draft-name', 'Dock priority')
                ->click('@draft-layout-option-answer.v1')->click('@inspector-sections')
                ->waitFor('[data-drag-section="references"]')
                ->assertNotPresent('[data-order-key="documents"]')
                ->click('#section-visible-references')
                ->drag('[data-drag-section="references"]', '[data-order-key="receiving"]')
                ->assertScript($readOrder, 'references,receiving,map')
                ->assertSeeIn('[data-order-key="references"]', 'Hidden')
                ->assertSeeIn('[data-order-key="receiving"]', 'First in order')
                ->drag('[data-drag-section="map"]', '[data-order-key="receiving"]')
                ->assertScript($readOrder, 'references,map,receiving')
                ->assertSeeIn('[data-order-key="map"]', 'First in order')
                ->click('@inspector-layout')->click('@draft-layout-option-journey.v1')
                ->click('@inspector-sections')->assertScript($readOrder, 'map,receiving,references')
                ->assertAttribute('#section-visible-references', 'aria-checked', 'true')
                ->click('@inspector-layout')->click('@draft-layout-option-answer.v1')
                ->click('@inspector-sections')->assertScript($readOrder, 'references,map,receiving')
                ->resize(390, 844)->assertMissing('[data-drag-section="map"]')
                ->keys('button[aria-label="Move Tracking map down"]', '{space}')
                ->assertScript($readOrder, 'references,receiving,map')
                ->assertSeeIn('[data-order-key="receiving"]', 'First in order')
                ->press('Save draft')->waitForLink('Preview saved revision 1')
                ->click('@inspector-sections')->assertScript($readOrder, 'references,receiving,map')
                ->assertAttribute('#section-visible-references', 'aria-checked', 'false');
        });

        $design = $company->drafts()->sole()->revisions()->sole()->design;
        $this->assertSame(['references', 'receiving', 'instructions', 'documents', 'map'], $design['section_orders']['answer.v1']);
        $this->assertSame(['references'], $design['hidden_modules']['answer.v1']);
        $this->assertSame(DesignCatalog::orders()['journey.v1'], $design['section_orders']['journey.v1']);
        $this->assertSame(0, $company->publications()->count());
    }
}
