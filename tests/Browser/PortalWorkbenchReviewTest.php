<?php

namespace Tests\Browser;

use App\Models\DesignProof;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PortalWorkbenchReviewTest extends DuskTestCase
{
    public function test_a_staff_designer_can_review_and_reopen_the_exact_pair_without_changing_classic(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeSuperAdmin();
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->resize(1440, 950)->loginAs($staff)->visit('/admin/themes')->waitForLink('Design prototypes')->clickLink('Design prototypes')->waitFor('#lookup-input')->screenshot('portal-review-lookup-desktop')
                ->type('#lookup-input', 'PRO-DEMO-7018')->press('Find shipment')->waitForText('Latest scan')
                ->press('Open company workspace')->waitForText('Classic')->assertSee('Shipment PRO-DEMO-7018')->press('Open proposal')
                ->waitFor('#library-version')->waitFor('iframe[title="Jayco shipment preview"]')->withinFrame('iframe[title="Jayco shipment preview"]', fn (Browser $frame) => $frame->waitFor('#journey-title'))->screenshot('portal-review-editor-desktop')->select('#library-version', '1')->type('#hex-brand', '#336699')->keys('#hex-brand', '{tab}')
                ->press('Advanced colors')->waitUntil('document.querySelector("#override-foreground").value.startsWith("#")')->assertSee('Good contrast')->type('#override-foreground', '#333344')->keys('#override-foreground', '{tab}')->assertInputValue('#override-foreground', '#333344')
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

    public function test_staff_can_compare_directions_across_the_portal_on_desktop_and_phone(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($staff) {
            $browser->loginAs($staff)->visit('/admin/portal-review')->waitForLink('Compare portal designs ↗')
                ->clickLink('Compare portal designs ↗')->waitFor('#dir-reference');

            $viewport = new ChromeDevToolsDriver($browser->driver);
            foreach (['manifest', 'fieldbook', 'signal'] as $direction) {
                $viewport->execute('Emulation.setDeviceMetricsOverride', ['width' => 1440, 'height' => 940, 'deviceScaleFactor' => 1, 'mobile' => false]);
                $browser->visit('/admin/portal-review/directions#'.$direction.'/lookup')
                    ->waitFor('.is-'.$direction.' .scan-row')->assertSee('Delivered')
                    ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth')
                    ->screenshot('directions-'.$direction.'-desktop')
                    ->type('#dir-reference', 'missing')->press('Look up')->waitForText('Shipment not found')
                    ->press('Use PRO-DEMO-7018')->waitFor('.scan-row')->press('Exceptions')
                    ->assertSeeIn('.scan-detail', 'Delivery exception')->press('All 6')
                    ->press('Jayco workspace')->waitFor('.company-profile')->assertSee('Classic')
                    ->click('button.file-row')->waitFor('iframe[title="Jayco shipment preview"]')
                    ->withinFrame('iframe', fn (Browser $frame) => $frame->waitFor('#journey-title'))
                    ->press('Trailhead')->assertSeeIn('.palette-slip', '#305E4A')
                    ->waitUntil('document.querySelector("iframe").contentWindow.location.search.includes("305E4A")')
                    ->withinFrame('iframe', fn (Browser $frame) => $frame->waitFor('#journey-title'))
                    ->screenshot('directions-'.$direction.'-studio')
                    ->click('#direction-nav a[href$="/themes"]')->waitForText('Theme library')
                    ->click('#direction-nav a[href$="/assets"]')->waitForText('Jayco collection')->press('Logo')
                    ->assertSeeIn('.asset-detail', 'Jayco wordmark')
                    ->click('#direction-nav a[href$="/access"]')->waitForText('Access directory')->click('.file-row:nth-of-type(3)')
                    ->assertSeeLink('Manage permissions');
                $viewport->execute('Emulation.setDeviceMetricsOverride', ['width' => 390, 'height' => 844, 'deviceScaleFactor' => 1, 'mobile' => true]);
                $browser->assertScript('innerWidth', 390)->press('Sections')->click('#direction-nav a[href$="/lookup"]')
                    ->waitFor('.scan-row')->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth')
                    ->screenshot('directions-'.$direction.'-mobile');
                if ($direction === 'signal') {
                    $browser->click('button[aria-label="Inspect scans in Macon, GA"]')->assertSeeIn('.scan-detail', 'Out for delivery');
                }
                $browser->press('Sections')->click('#direction-nav a[href$="/companies"]')->waitFor('.company-profile')
                    ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth');
            }

            $browser->click('nav[aria-label="Design directions"] a[href^="#manifest"]')
                ->waitFor('.is-manifest .company-profile')->assertFragmentIs('manifest/companies')
                ->back()->waitFor('.is-signal .company-profile');
            $viewport->execute('Emulation.clearDeviceMetricsOverride');
        });
    }

    public function test_lookup_only_staff_see_only_the_lookup_in_every_direction(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeStandardUser();
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->loginAs($staff);
            foreach (['manifest', 'fieldbook', 'signal'] as $direction) {
                $browser->visit('/admin/portal-review/directions#'.$direction.'/access')
                    ->waitFor('#dir-reference')->assertMissing('#direction-nav a[href$="/access"]')
                    ->assertMissing('.dir-link[href*="users"]');
            }
        });
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
