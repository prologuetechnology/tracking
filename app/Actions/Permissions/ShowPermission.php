<?php

namespace App\Actions\Permissions;

use Spatie\Permission\Models\Permission;

class ShowPermission
{
    public function execute(Permission $permission): Permission
    {
        return $permission->refresh();
    }
}
