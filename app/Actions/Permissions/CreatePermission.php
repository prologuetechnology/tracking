<?php

namespace App\Actions\Permissions;

use Spatie\Permission\Models\Permission;

class CreatePermission
{
    public function execute(array $attributes): Permission
    {
        return Permission::query()->create($attributes);
    }
}
