<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPageContractsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures(withImageTypes: true);
    }

    public function test_super_admin_pages_render_with_expected_hydrated_payloads(): void
    {
        $superAdmin = $this->makeSuperAdmin(['email' => 'admin-pages@example.test']);
        $managedUser = $this->makeStandardUser(['email' => 'managed-user@example.test']);
        $role = Role::findByName('Company Admin');
        $permission = Permission::findByName('company:show');

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Index')
                ->has('initialUsers', 2)
                ->has('initialRoles', 3)
                ->where('initialUsers.0.email', 'admin-pages@example.test')
                ->where('initialUsers.1.email', 'managed-user@example.test')
                ->where('initialRoles.0.name', 'Company Admin'));

        $this->actingAs($superAdmin)
            ->get(route('admin.role.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/role/Index')
                ->has('initialRoles', 3)
                ->where('initialRoles.0.name', 'Company Admin'));

        $this->actingAs($superAdmin)
            ->get(route('admin.role.show', $role))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/role/Edit')
                ->where('initialRole.name', 'Company Admin')
                ->has('initialPermissions')
                ->where('initialPermissions.0.name', 'allowed_domain:destroy'));

        $this->actingAs($superAdmin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/permissions/Index')
                ->has('initialPermissions')
                ->where('initialPermissions.0.name', 'allowed_domain:destroy'));

        $this->actingAs($superAdmin)
            ->get(route('admin.permissions.show', $permission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/permissions/Edit')
                ->where('initialPermission.name', 'company:show'));
    }

    public function test_super_admin_only_pages_reject_non_super_admin_users(): void
    {
        $user = $this->makeStandardUser(['email' => 'not-super-admin@example.test']);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.role.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.permissions.index'))->assertForbidden();
    }

    public function test_image_library_page_requires_image_permission_and_returns_hydrated_data(): void
    {
        $image = $this->makeImage(attributes: ['name' => 'Hydrated Logo']);
        $viewer = $this->makeUserWithPermission('image:show', ['email' => 'image-viewer@example.test']);
        $deniedUser = $this->makeStandardUser(['email' => 'image-denied@example.test']);

        $this->actingAs($deniedUser)
            ->get(route('admin.image.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('admin.image.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/image/Index')
                ->has('initialImages', 1)
                ->has('initialImageTypes', 5)
                ->where('initialImages.0.id', $image->id)
                ->where('initialImages.0.name', 'Hydrated Logo')
                ->where('initialImageTypes.0.name', 'logo'));
    }
}
