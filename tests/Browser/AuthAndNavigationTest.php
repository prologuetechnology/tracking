<?php

namespace Tests\Browser;

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
                ->click('@user-menu-trigger')
                ->waitForText('Allowed Domains')
                ->assertSee('Users')
                ->assertSee('Roles')
                ->click('@sign-out')
                ->pause(500)
                ->assertPathIs('/login')
                ->assertSee('Login with Microsoft');
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
                ->assertSee('Sign out');
        });
    }
}
