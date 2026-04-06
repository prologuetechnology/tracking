<?php

namespace App\Actions\Permissions;

use Spatie\Permission\Models\Permission;

class UpdatePermission
{
    public function execute(Permission $permission, array $attributes): Permission
    {
        $permission->update($attributes);

        return $permission->refresh();
    }
}
