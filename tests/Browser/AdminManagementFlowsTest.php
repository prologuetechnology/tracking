<?php

namespace Tests\Browser;

use App\Enums\ImageTypeEnum;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminManagementFlowsTest extends DuskTestCase
{
    public function test_super_admin_can_create_a_company_from_the_browser(): void
    {
        $this->seedCoreFixtures();

        $user = $this->makeSuperAdmin(['email' => 'company-flow@example.com']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/companies/create')
                ->waitForText('Create Company')
                ->type('@company-name', 'Dusk Logistics')
                ->type('@company-pipeline-id', '6543')
                ->type('@company-website', 'https://dusk-logistics.test')
                ->type('@company-phone', '555-222-3333')
                ->type('@company-email', 'ops@dusk-logistics.test')
                ->click('@company-form-save')
                ->waitForText('Dusk Logistics')
                ->assertPathBeginsWith('/admin/companies/')
                ->assertSee('Company Details');
        });
    }

    public function test_super_admin_can_create_edit_toggle_and_delete_allowed_domains(): void
    {
        $this->seedCoreFixtures();

        $user = $this->makeSuperAdmin(['email' => 'domains-flow@example.com']);
        $existing = $this->makeAllowedDomain($user, [
            'domain' => 'existing-domain.test',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($user, $existing) {
            $browser->loginAs($user)
                ->visit('/admin/allowed-domains')
                ->waitForText('Allowed Domains')
                ->click('@allowed-domain-open-create')
                ->waitForText('Create Allowed Domain')
                ->type('@allowed-domain-name', 'created-domain.test')
                ->click('@allowed-domain-submit-create')
                ->waitForText('created-domain.test')
                ->click("@allowed-domain-edit-{$existing->id}")
                ->waitForText('Edit Allowed Domain')
                ->clear('@allowed-domain-name')
                ->type('@allowed-domain-name', 'updated-domain.test')
                ->click("@allowed-domain-save-{$existing->id}")
                ->waitForText('updated-domain.test')
                ->click("@allowed-domain-toggle-{$existing->id}")
                ->pause(500)
                ->click("@allowed-domain-delete-{$existing->id}")
                ->waitForText('Delete Allowed Domain')
                ->click("@allowed-domain-confirm-delete-{$existing->id}")
                ->pause(1000)
                ->assertMissing("@allowed-domain-edit-{$existing->id}")
                ->assertMissing("@allowed-domain-delete-{$existing->id}");
        });

        $this->assertDatabaseHas('allowed_domains', [
            'domain' => 'created-domain.test',
        ]);
        $this->assertSoftDeleted('allowed_domains', [
            'id' => $existing->id,
        ]);
        $this->assertDatabaseHas('allowed_domains', [
            'id' => $existing->id,
            'domain' => 'updated-domain.test',
            'is_active' => 0,
        ]);
    }

    public function test_super_admin_can_upload_an_image_and_manage_permissions_and_users(): void
    {
        $this->seedCoreFixtures(withImageTypes: true);

        $user = $this->makeSuperAdmin(['email' => 'rbac-flow@example.com']);
        $targetUser = $this->makeStandardUser(['email' => 'impersonation-target@example.com']);
        $uploadPath = dirname(__DIR__).'/Browser/source/test-upload.svg';

        $this->browse(function (Browser $browser) use ($user, $targetUser, $uploadPath) {
            $browser->loginAs($user)
                ->visit('/admin/images')
                ->waitForText('Manage Images')
                ->click('@image-upload-open')
                ->waitForText('Upload Image')
                ->type('@image-upload-name', 'Browser Logo')
                ->click('@image-upload-type-trigger')
                ->waitFor('@image-upload-type-logo')
                ->click('@image-upload-type-logo')
                ->attach('@image-upload-file', $uploadPath)
                ->click('@image-upload-save')
                ->waitForText('Browser Logo')
                ->visit('/admin/permissions/create')
                ->waitForText('Create Permission')
                ->type('@permission-name', 'reports:dusk')
                ->type('@permission-guard', 'web')
                ->click('@permission-save')
                ->pause(500)
                ->assertPathIs('/admin/permissions')
                ->assertSee('reports:dusk')
                ->visit('/admin/users')
                ->waitForText('Users')
                ->click("@user-role-trigger-{$targetUser->id}")
                ->waitFor("@user-role-option-company-admin")
                ->click("@user-role-option-company-admin")
                ->pause(500)
                ->assertSee('Company Admin')
                ->click("@impersonate-user-{$targetUser->id}")
                ->waitForText('Impersonation Active')
                ->assertPathIs('/admin/companies')
                ->click('@user-menu-trigger')
                ->waitForText('Stop Impersonation')
                ->click('@stop-impersonation')
                ->pause(500)
                ->assertPathIs('/admin/companies')
                ->assertSee('Companies');
        });

        $this->assertDatabaseHas('images', ['name' => 'Browser Logo']);
        $this->assertTrue(User::query()->findOrFail($targetUser->id)->hasRole('Company Admin'));
    }
}
