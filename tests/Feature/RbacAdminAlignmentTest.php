<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacAdminAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_users_page_is_hydrated_with_initial_users_and_roles(): void
    {
        $superAdmin = $this->createSuperAdmin('admin@example.com');
        $employee = User::factory()->create(['email' => 'user@example.com']);
        $employee->syncRoles(['Company Admin']);

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Index')
                ->has('initialUsers', 2)
                ->has('initialRoles', 3)
                ->where('initialUsers.0.email', 'admin@example.com')
                ->where('initialUsers.1.email', 'user@example.com'));
    }

    public function test_super_admin_roles_pages_are_hydrated_with_initial_role_data(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $role = Role::findByName('Company Admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.role.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/role/Index')
                ->has('initialRoles', 3));

        $this->actingAs($superAdmin)
            ->get(route('admin.role.show', $role))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/role/Edit')
                ->where('initialRole.name', 'Company Admin')
                ->has('initialPermissions', 29));
    }

    public function test_super_admin_permissions_pages_are_hydrated_with_initial_permission_data(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $permission = Permission::findByName('permission:show');

        $this->actingAs($superAdmin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/permissions/Index')
                ->has('initialPermissions', 29));

        $this->actingAs($superAdmin)
            ->get(route('admin.permissions.show', $permission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/permissions/Edit')
                ->where('initialPermission.name', 'permission:show'));
    }

    public function test_non_super_admins_cannot_access_super_admin_rbac_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.permissions.index'))
            ->assertForbidden();
    }

    public function test_user_role_updates_return_a_flat_resource_shape(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($superAdmin)
            ->patchJson(route('api.admin.users.update.role', $user), [
                'role' => [
                    'name' => 'Company Admin',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('roles.0.name', 'Company Admin')
            ->assertJsonMissingPath('roles.data');
    }

    public function test_role_permission_sync_returns_a_flat_role_shape(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $role = Role::findByName('Company Admin');

        $this->actingAs($superAdmin)
            ->putJson(route('api.admin.roles.assignPermissions', $role), [
                'permissions' => ['company:show', 'theme:show'],
            ])
            ->assertOk()
            ->assertJsonPath('role.name', 'Company Admin')
            ->assertJsonPath('role.permissions.0.name', 'company:show')
            ->assertJsonMissingPath('role.permissions.data');
    }

    public function test_permission_crud_returns_flat_resources(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $created = $this->actingAs($superAdmin)
            ->postJson(route('api.admin.permissions.store'), [
                'name' => 'reports:show',
                'guard_name' => 'web',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'reports:show')
            ->assertJsonMissingPath('data')
            ->json();

        $this->actingAs($superAdmin)
            ->putJson(route('api.admin.permissions.update', $created['id']), [
                'name' => 'reports:update',
                'guard_name' => 'web',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'reports:update')
            ->assertJsonMissingPath('data');

        $this->actingAs($superAdmin)
            ->deleteJson(route('api.admin.permissions.destroy', $created['id']))
            ->assertNoContent();
    }

    private function createSuperAdmin(string $email = 'super-admin@example.com'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->syncRoles(['Super Admin']);

        return $user;
    }
}
