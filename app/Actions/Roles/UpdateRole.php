<?php

namespace App\Actions\Roles;

use Spatie\Permission\Models\Role;

class UpdateRole
{
    public function execute(Role $role, array $attributes): Role
    {
        $role->update([
            'name' => $attributes['name'],
        ]);

        if (array_key_exists('permissions', $attributes)) {
            $role->syncPermissions($attributes['permissions']);
        }

        return $role->load(ListRoles::RELATIONS);
    }
}
