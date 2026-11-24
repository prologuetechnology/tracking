<?php

namespace Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthAndNavigationTest extends DuskTestCase
{
    public function test_login_page_renders_and_super_admin_can_sign_out_from_the_shell(): void
    {
        $this->seedCoreFixtures();

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Login with Microsoft')
                ->assertSee('Login with Microsoft')
                ->visit('/testing/oauth-login')
                ->waitForText('Companies')
                ->clickLink('Application Settings')->waitForText('Access')
                ->click('a[href$="/settings/access/users"]')->waitForLocation('/admin/settings/access/users')
                ->assertSeeIn('nav[aria-label="Access administration"]', 'Allowed Domains')
                ->click('@user-menu-trigger')->waitFor('@staff-appearance-menu')
                ->assertDontSeeIn('[role="menu"]', 'Allowed Domains')
                ->click('@sign-out')
                ->pause(500)
                ->assertPathIs('/login')
                ->assertSee('Login with Microsoft');
        });
    }

    public function test_appearance_follows_the_device_and_account_across_staff_documents(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeSuperAdmin();
        $this->browse(function (Browser $browser) use ($staff) {
            $viewport = new ChromeDevToolsDriver($browser->driver);
            $emulate = fn (string $value) => $viewport->execute('Emulation.setEmulatedMedia', ['features' => [['name' => 'prefers-color-scheme', 'value' => $value]]]);
            try {
                $viewport->execute('Emulation.setDeviceMetricsOverride', ['width' => 1440, 'height' => 900, 'deviceScaleFactor' => 1, 'mobile' => false]);
                $emulate('dark');
                $browser->loginAs($staff)->visit('/admin/tracking')->waitFor('@user-menu-trigger')->waitUntil('document.documentElement.matches(\'html.dark[data-staff-appearance="system"]\')');
                $emulate('light');
                $browser->waitUntil('!document.documentElement.classList.contains("dark")')->click('@user-menu-trigger')->waitFor('@staff-appearance-menu')->keys('@staff-appearance-menu', '{arrow_right}')->waitFor('@appearance-system')->assertAttribute('@appearance-system', 'aria-checked', 'true')->waitFor('@appearance-dark')->click('@appearance-dark')->waitUntil('document.documentElement.matches(\'html.dark[data-staff-appearance="dark"]\')')
                    ->visit('/admin/themes')->waitUntil('document.documentElement.matches(\'html.dark[data-staff-appearance="dark"]\')')
                    ->visit('/trackShipment/notFound/sample')->waitForText('Shipment not found')->assertScript('!document.documentElement.hasAttribute("data-staff-appearance")');
                $viewport->execute('Emulation.setDeviceMetricsOverride', ['width' => 390, 'height' => 844, 'deviceScaleFactor' => 1, 'mobile' => true]);
                $browser->visit('/admin/tracking')->press('Sections')->waitFor('[role="dialog"] nav')->assertDontSeeIn('[role="dialog"]', 'Appearance')->keys('[role="dialog"]', '{escape}')->waitUntilMissing('[role="dialog"]')
                    ->click('@user-menu-trigger')->waitFor('@staff-appearance-menu')->click('@staff-appearance-menu')->waitFor('@appearance-dark')->assertAttribute('@appearance-dark', 'aria-checked', 'true')->waitFor('@appearance-light')->click('@appearance-light')
                    ->waitUntil('document.documentElement.dataset.staffAppearance === "light"')->assertScript('!document.documentElement.classList.contains("dark")')->assertScript('document.documentElement.scrollWidth === innerWidth');
            } finally {
                $viewport->execute('Emulation.clearDeviceMetricsOverride');
                $viewport->execute('Emulation.setEmulatedMedia', ['features' => []]);
            }
        });
    }

    public function test_style_library_staff_can_enter_studio_without_company_permissions(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeUserWithPermission('theme:show');
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->resize(1440, 900)->loginAs($staff)->visit('/admin/tracking')
                ->waitForLink('Shared Assets')->assertDontSeeLink('Design Studio')->clickLink('Shared Assets')
                ->waitForLocation('/admin/design-studio/styles')->waitFor('#find-theme')
                ->assertDontSeeLink('Companies')->assertDontSeeLink('Create style')
                ->assertMissing('@theme-form-save');
        });
    }

    public function test_company_admin_navigation_hides_super_admin_menu_items(): void
    {
        $this->seedCoreFixtures();

        $companyAdmin = $this->makeCompanyAdmin([
            'email' => 'company-admin-dusk@example.com',
        ]);
        $companyAdmin->givePermissionTo(['company:show', 'theme:show', 'image:show']);

        $this->browse(function (Browser $browser) use ($companyAdmin) {
            $browser->loginAs($companyAdmin)
                ->visit('/admin/companies')
                ->waitForText('Companies')
                ->click('@user-menu-trigger')
                ->waitForText($companyAdmin->email)
                ->assertDontSee('Allowed Domains')
                ->assertDontSee('Users')
                ->assertDontSee('Roles')
                ->assertSee('Sign Out');
        });
    }
}
