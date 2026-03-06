<?php

namespace App\Actions\Roles;

use Spatie\Permission\Models\Role;

class SyncRolePermissions
{
    public function execute(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $role->load(ListRoles::RELATIONS);
    }
}
