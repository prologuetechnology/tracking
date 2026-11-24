<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminSmokeTest extends DuskTestCase
{
    public function test_oauth_test_login_reaches_the_staff_shipment_console(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/testing/oauth-login')
                ->waitForText('Shipment Lookup')
                ->assertPathIs('/admin/tracking')
                ->assertPresent('@tracking-number-input');
        });
    }

    public function test_super_admin_can_load_the_permission_create_screen(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/testing/oauth-login')
                ->visit('/admin/permissions/create')
                ->waitForText('Create Permission')
                ->assertPathIs('/admin/settings/access/permissions/create');
        });
    }
}
