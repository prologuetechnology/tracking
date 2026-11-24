<?php

namespace Tests\Browser;

use App\Enums\ImageTypeEnum;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class AdminManagementFlowsTest extends DuskTestCase
{
    public function test_read_only_staff_can_inspect_a_company_without_management_controls(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeStandardUser();
        $staff->givePermissionTo('company:show');
        $company = $this->makeCompany(['name' => 'Northline Supply']);
        $this->browse(function (Browser $browser) use ($staff, $company) {
            $browser->loginAs($staff)->visit(route('admin.companies.show', $company->uuid))
                ->waitForText('Northline Supply')->assertSeeIn('[aria-label="Company details"]', 'Pipeline ID')
                ->assertSee('Live design')->assertMissing('@company-form-save')->assertMissing('#is_active')
                ->assertDontSeeLink('Design drafts');
        });
    }

    public function test_staff_can_assign_role_permissions_with_the_keyboard(): void
    {
        $this->seedCoreFixtures();
        $staff = $this->makeSuperAdmin();
        $role = Role::findByName('Company Admin');
        $permission = Permission::create(['name' => 'fixture:review', 'guard_name' => 'web']);

        $this->browse(function (Browser $browser) use ($staff, $role, $permission) {
            $checkbox = '[role="checkbox"][aria-label="Assign '.$permission->name.'"]';
            $browser->loginAs($staff)->visit(route('admin.role.show', $role))
                ->waitFor($checkbox)->assertAttribute($checkbox, 'aria-checked', 'false')
                ->keys($checkbox, '{space}')->waitFor($checkbox.'[aria-checked="true"]')
                ->press('Save')->waitForLocation(route('admin.role.index', absolute: false));
        });

        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
    }

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

    public function test_super_admin_can_create_and_update_themes_from_the_browser(): void
    {
        $this->seedCoreFixtures();

        $user = $this->makeSuperAdmin(['email' => 'theme-flow@example.com']);
        $existingTheme = $this->makeTheme(['name' => 'Existing Browser Theme']);

        $this->browse(function (Browser $browser) use ($user, $existingTheme) {
            $browser->loginAs($user)
                ->visit('/admin/themes/create')
                ->waitForText('Create a style')->assertPathIs('/admin/design-studio/styles/create')
                ->type('@theme-name', 'Browser Theme')
                ->click('@theme-form-save')
                ->waitUntil("window.location.pathname === '/admin/design-studio/styles'")
                ->waitForText('Browser Theme')
                ->click("@theme-edit-{$existingTheme->id}")
                ->waitForText('Existing Browser Theme')
                ->assertSee('Version to inspect')
                ->clear('@theme-name')
                ->type('@theme-name', 'Updated Browser Theme')
                ->click('@theme-form-save')
                ->waitUntil("window.location.pathname === '/admin/design-studio/styles'")
                ->waitForText('Updated Browser Theme')
                ->click("@theme-edit-{$existingTheme->id}")->waitFor('#library-history')
                ->assertSeeIn('#library-history', 'v2')->click('#library-history')
                ->waitFor('@library-history-option-'.$existingTheme->versions()->where('version', 1)->value('id'))
                ->click('@library-history-option-'.$existingTheme->versions()->where('version', 1)->value('id'))
                ->assertSeeIn('#library-history', 'v1');
        });

        $this->assertDatabaseHas('themes', ['name' => 'Browser Theme']);
        $this->assertDatabaseHas('themes', [
            'id' => $existingTheme->id,
            'name' => 'Updated Browser Theme',
        ]);
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
                ->type('#asset-search', 'Browser Logo')
                ->assertSeeIn('aside[aria-label="Asset inspector"]', 'Browser Logo')
                ->visit('/admin/permissions/create')
                ->waitForText('Create Permission')
                ->assertSeeIn('nav[aria-label="Access administration"]', 'Allowed domains')
                ->type('@permission-name', 'reports:dusk')
                ->type('@permission-guard', 'web')
                ->click('@permission-save')
                ->pause(500)
                ->assertPathIs('/admin/permissions')
                ->assertSee('reports:dusk')
                ->visit('/admin/users')
                ->waitForText('Users')
                ->click("@user-role-trigger-{$targetUser->id}")
                ->waitFor('@user-role-option-company-admin')
                ->click('@user-role-option-company-admin')
                ->pause(500)
                ->assertSee('Company Admin')
                ->click("@impersonate-user-{$targetUser->id}")
                ->waitForText('Acting as')
                ->assertPathIs('/admin/tracking')
                ->click('@user-menu-trigger')
                ->waitForText('Stop Impersonation')
                ->click('@stop-impersonation')
                ->pause(500)
                ->assertPathIs('/admin/tracking')
                ->assertSee('Shipment console');
        });

        $this->assertDatabaseHas('images', ['name' => 'Browser Logo']);
        $this->assertTrue(User::query()->findOrFail($targetUser->id)->hasRole('Company Admin'));
    }

    public function test_company_brand_changes_open_in_drafts_and_assigned_artwork_is_protected(): void
    {
        $this->seedCoreFixtures(withImageTypes: true);

        $user = $this->makeSuperAdmin(['email' => 'shared-images@example.com']);
        $sharedLogo = $this->makeImage(ImageTypeEnum::LOGO->value, [
            'name' => 'Shared Browser Logo',
        ]);
        $firstCompany = $this->makeCompany([
            'name' => 'Shared First Company',
            'pipeline_company_id' => 991001,
            'logo_image_id' => $sharedLogo->id,
        ]);
        $secondCompany = $this->makeCompany([
            'name' => 'Shared Second Company',
            'pipeline_company_id' => 991002,
            'email' => 'shared-second@example.com',
        ]);

        $this->browse(function (Browser $browser) use ($user, $sharedLogo, $firstCompany, $secondCompany) {
            $browser->loginAs($user)
                ->visit("/admin/companies/{$secondCompany->uuid}")
                ->waitForText('Shared Second Company')
                ->assertMissing('@company-image-open-logo')
                ->visit("/admin/companies/{$firstCompany->uuid}")
                ->waitForText('Shared First Company')
                ->assertMissing('@company-image-clear-logo')
                ->visit('/admin/images')
                ->waitForText('Manage Images')
                ->click("@image-delete-open-{$sharedLogo->id}")
                ->waitForText('currently assigned to 1 company')
                ->assertDisabled("@image-delete-confirm-{$sharedLogo->id}");
        });

        $this->assertDatabaseHas('companies', [
            'id' => $firstCompany->id,
            'logo_image_id' => $sharedLogo->id,
        ]);
        $this->assertDatabaseHas('companies', [
            'id' => $secondCompany->id,
            'logo_image_id' => null,
        ]);
        $this->assertDatabaseHas('images', [
            'id' => $sharedLogo->id,
        ]);
    }
}
