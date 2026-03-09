<?php

namespace Tests\Unit;

use App\Actions\Permissions\CreatePermission;
use App\Actions\Permissions\ListPermissions;
use App\Actions\Permissions\ShowPermission;
use App\Actions\Permissions\UpdatePermission;
use App\Actions\Roles\CreateRole;
use App\Actions\Roles\ListRoles;
use App\Actions\Roles\ShowRole;
use App\Actions\Roles\SyncRolePermissions;
use App\Actions\Roles\UpdateRole;
use App\Actions\Themes\CreateTheme;
use App\Actions\Themes\ListThemes;
use App\Actions\Themes\UpdateTheme;
use App\Actions\Users\ListUsers;
use App\Actions\Users\ShowUser;
use App\Actions\Users\UpdateUserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAndThemeActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures();
    }

    public function test_it_creates_updates_and_lists_themes(): void
    {
        $theme = (new CreateTheme)->execute([
            'name' => 'Ocean',
            'primary_hue' => [210],
            'primary_saturation' => [70],
            'primary_lightness' => [40],
            'accent_hue' => [18],
            'accent_saturation' => [80],
            'accent_lightness' => [55],
            'derive_from' => 'primary',
        ]);

        $updated = (new UpdateTheme)->execute($theme, [
            'name' => 'Sunset',
            'primary_hue' => [320],
            'primary_saturation' => [70],
            'primary_lightness' => [40],
            'accent_hue' => [18],
            'accent_saturation' => [80],
            'accent_lightness' => [55],
            'derive_from' => 'accent',
        ]);

        $this->assertSame('Sunset', $updated->name);
        $this->assertCount(1, (new ListThemes)->execute());
    }

    public function test_it_manages_permissions_roles_and_user_roles(): void
    {
        $permission = (new CreatePermission)->execute([
            'name' => 'reports:show',
            'guard_name' => 'web',
        ]);

        $updatedPermission = (new UpdatePermission)->execute($permission, [
            'name' => 'reports:update',
            'guard_name' => 'web',
        ]);

        $role = (new CreateRole)->execute([
            'name' => 'Reports Admin',
            'permissions' => ['reports:update'],
        ]);

        $updatedRole = (new UpdateRole)->execute($role, [
            'name' => 'Reports Lead',
            'permissions' => ['reports:update', 'company:show'],
        ]);

        $syncedRole = (new SyncRolePermissions)->execute($updatedRole, ['company:show']);

        $user = $this->makeStandardUser();
        $updatedUser = (new UpdateUserRole)->execute($user, 'Company Admin');

        $this->assertSame('reports:update', $updatedPermission->name);
        $this->assertSame('Reports Lead', (new ShowRole)->execute($updatedRole)->name);
        $this->assertSame(['company:show'], $syncedRole->permissions->pluck('name')->all());
        $this->assertSame('Company Admin', (new ShowUser)->execute($updatedUser)->roles->first()->name);
        $this->assertContains('reports:update', (new ListPermissions)->execute()->pluck('name')->all());
        $this->assertContains('Reports Lead', (new ListRoles)->execute()->pluck('name')->all());
        $this->assertContains($updatedUser->email, (new ListUsers)->execute()->pluck('email')->all());
    }
}
