<?php

namespace App\Actions\Roles;

use Spatie\Permission\Models\Role;

class CreateRole
{
    public function execute(array $attributes): Role
    {
        $role = Role::query()->create([
            'name' => $attributes['name'],
        ]);

        if (! empty($attributes['permissions'])) {
            $role->syncPermissions($attributes['permissions']);
        }

        return $role->load(ListRoles::RELATIONS);
    }
}
