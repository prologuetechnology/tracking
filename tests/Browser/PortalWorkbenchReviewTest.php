<?php

namespace Tests\Browser;

use App\Models\DesignProof;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PortalWorkbenchReviewTest extends DuskTestCase
{
    public function test_a_staff_designer_can_review_and_reopen_the_exact_pair_without_changing_classic(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeSuperAdmin();
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->resize(1440, 950)->loginAs($staff)->visit('/admin/themes')->waitForLink('Preview new staff portal')->clickLink('Preview new staff portal')->waitFor('#lookup-input')->screenshot('portal-review-lookup-desktop')
                ->type('#lookup-input', 'PRO-DEMO-7018')->press('Find shipment')->waitForText('Latest scan')
                ->press('Open company workspace')->waitForText('Classic')->assertSee('Shipment PRO-DEMO-7018')->press('Open proposal')
                ->waitFor('#library-version')->waitFor('iframe[title="Jayco shipment preview"]')->withinFrame('iframe[title="Jayco shipment preview"]', fn (Browser $frame) => $frame->waitFor('#journey-title'))->screenshot('portal-review-editor-desktop')->select('#library-version', '1')->type('#hex-brand', '#336699')->click('label[for="headline"]')
                ->click('fieldset.inspector summary')->waitUntil('document.querySelector("#override-foreground").value.startsWith("#")')->assertSee('Good contrast')->type('#override-foreground', '#333344')->click('label[for="corners"]')
                ->press('Save theme version')->waitForText('Trailhead version 2 saved')
                ->press('Use in draft')->waitForText('Applied style: Trailhead · v2')
                ->press('Change banner')->waitForText('Choose a banner')->type('#find-asset', 'landscape')
                ->press('Use in proposal')->waitForText('Canyon landscape selected')
                ->resize(390, 844)->press('Preview')->waitFor('iframe[title="Jayco shipment preview"]')
                ->withinFrame('iframe[title="Jayco shipment preview"]', fn (Browser $frame) => $frame->waitFor('#journey-title')->assertSee('Byron'))
                ->click('button[aria-label="Mobile preview"]')->screenshot('portal-review-editor-mobile')->press('Edit')->press('Save revision')
                ->waitForText('Revision 1 proofs')->press('Export desktop and mobile PNGs')
                ->waitFor('button[aria-label="Inspect desktop proof"]', 90)
                ->waitUntil('document.querySelector(".proof-image img").naturalWidth===1440')
                ->assertScript('document.querySelectorAll(".proof-image img")[1].naturalWidth', 390);
            $original = $browser->attribute('.proof-image img', 'src');
            $mobile = $browser->attribute('button[aria-label="Inspect mobile proof"] img', 'src');
            $browser->type('#reviewer', 'Sample customer')->press('Record review approval')->waitForText('Recorded for Sample customer')
                ->refresh()->waitForText('Revision 1 proofs')->assertAttribute('.proof-image img', 'src', $original)
                ->press('Jayco workspace')->waitForText('Approved')->assertSee('Classic')
                ->click('.history-row')->waitForText('Revision 1 proofs')->assertAttribute('.proof-image img', 'src', $original)
                ->assertAttribute('button[aria-label="Inspect mobile proof"] img', 'src', $mobile)
                ->assertScript('document.documentElement.scrollWidth <= innerWidth');
        });
        $this->assertSame(0, DesignProof::count());
    }

    public function test_public_and_staff_footers_span_the_page_and_follow_the_content(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeCompanyAdmin();
        $studio = route('admin.drafts.index', $this->makeCompany()->uuid, false);
        $this->browse(function (Browser $browser) use ($staff, $studio) {
            $browser->loginAs($staff);
            foreach ([1440, 390] as $width) {
                $browser->resize($width, 900);
                foreach (['/admin/themes' => 'staff', $studio => 'staff', '/trackShipment/notFound/DEMO-MISSING' => 'tracking', '/admin/portal-review#companies' => 'staff', '/admin/portal-review/classic' => 'tracking'] as $path => $kind) {
                    $footer = '[data-'.$kind.'-footer]';
                    $browser->visit($path)->waitFor($footer)->assertPresent($footer);
                    $browser->assertScript('document.querySelector("footer").offsetWidth === document.documentElement.clientWidth')
                        ->assertScript('Math.abs(document.querySelector("footer").getBoundingClientRect().bottom + scrollY - Math.max(innerHeight,document.documentElement.scrollHeight)) < 3')
                        ->assertScript('!["fixed","sticky"].includes(getComputedStyle(document.querySelector("footer")).position)')
                        ->assertScript('document.documentElement.scrollWidth <= innerWidth');
                    if ($path === '/admin/portal-review/classic') {
                        $browser->assertScript('document.querySelector("footer").getBoundingClientRect().top >= innerHeight');
                    }
                    if ($path === '/admin/portal-review#companies') {
                        $browser->waitFor('#find-company')->assertScript('Math.abs(document.querySelector("footer").getBoundingClientRect().bottom - innerHeight) < 3');
                    }
                    if ($kind === 'tracking') {
                        $browser->assertScript('document.querySelector("footer").getBoundingClientRect().top >= document.querySelector("main").getBoundingClientRect().bottom');
                    }
                }
            }
        });
    }
}
