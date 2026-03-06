<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminSmokeTest extends DuskTestCase
{
    public function test_oauth_test_login_reaches_the_authenticated_companies_shell(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/testing/oauth-login')
                ->waitForText('Companies')
                ->assertPathIs('/admin/companies')
                ->assertSee('Companies');
        });
    }

    public function test_super_admin_can_open_the_permission_create_screen(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/testing/oauth-login')
                ->visit('/admin/permissions')
                ->waitForText('Permissions')
                ->assertSee('Create Permission')
                ->clickLink('Create Permission')
                ->waitForText('Create Permission')
                ->assertPathIs('/admin/permissions/create');
        });
    }
}
