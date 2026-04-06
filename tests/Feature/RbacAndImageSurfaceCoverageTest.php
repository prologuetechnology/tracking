<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacAndImageSurfaceCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_api_surfaces_cover_role_user_and_permission_flows(): void
    {
        $this->seedCoreFixtures();

        $superAdmin = $this->makeSuperAdmin();
        $user = $this->makeStandardUser();
        $role = Role::findByName('Company Admin');
        $permission = Permission::findByName('company:show');

        $createdRole = $this->actingAs($superAdmin)
            ->postJson(route('api.admin.roles.store'), [
                'name' => 'Audit Lead',
                'permissions' => ['company:show'],
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Audit Lead')
            ->json();

        $this->actingAs($superAdmin)
            ->getJson(route('api.admin.roles.show', $role))
            ->assertOk()
            ->assertJsonPath('name', $role->name);

        $this->actingAs($superAdmin)
            ->putJson(route('api.admin.roles.update', $createdRole['id']), [
                'name' => 'Audit Manager',
                'permissions' => ['company:show', 'theme:show'],
            ])
            ->assertOk()
            ->assertJsonPath('permissions.1.name', 'theme:show');

        $this->actingAs($superAdmin)
            ->putJson(route('api.admin.roles.assignPermissions', $role), [
                'permissions' => ['company:show'],
            ])
            ->assertOk()
            ->assertJsonPath('role.permissions.0.name', 'company:show');

        $this->actingAs($superAdmin)
            ->getJson(route('api.admin.users.index'))
            ->assertOk()
            ->assertJsonFragment(['email' => $user->email]);

        $this->actingAs($superAdmin)
            ->getJson(route('api.admin.users.show', $user))
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->actingAs($superAdmin)
            ->patchJson(route('api.admin.users.update.role', $user), [
                'role' => ['name' => 'Company Admin'],
            ])
            ->assertOk()
            ->assertJsonPath('roles.0.name', 'Company Admin');

        $this->actingAs($superAdmin)
            ->getJson(route('api.admin.permissions.show', $permission))
            ->assertOk()
            ->assertJsonPath('name', 'company:show');

        $createdPermission = $this->actingAs($superAdmin)
            ->postJson(route('api.admin.permissions.store'), [
                'name' => 'audit:show',
                'guard_name' => 'web',
            ])
            ->assertCreated()
            ->json();

        $this->actingAs($superAdmin)
            ->putJson(route('api.admin.permissions.update', $createdPermission['id']), [
                'name' => 'audit:update',
                'guard_name' => 'web',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'audit:update');

        $this->actingAs($superAdmin)
            ->deleteJson(route('api.admin.permissions.destroy', $createdPermission['id']))
            ->assertNoContent();

        $this->actingAs($superAdmin)
            ->deleteJson(route('api.admin.roles.destroy', $createdRole['id']))
            ->assertNoContent();
    }

    public function test_image_type_index_is_permission_gated_and_returns_a_flat_contract(): void
    {
        $this->seedCoreFixtures(withImageTypes: true);

        $this->getJson(route('api.imageTypes.index'))->assertUnauthorized();

        $user = $this->makeStandardUser();
        $this->actingAs($user)->getJson(route('api.imageTypes.index'))->assertForbidden();

        $viewer = $this->makeUserWithPermission('image:show');

        $this->actingAs($viewer)
            ->getJson(route('api.imageTypes.index'))
            ->assertOk()
            ->assertJsonCount(5)
            ->assertJsonPath('0.name', 'logo');
    }
}
