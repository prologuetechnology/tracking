<?php

namespace App\Actions\Roles;

use Spatie\Permission\Models\Role;

class ShowRole
{
    public function execute(Role $role): Role
    {
        return $role->load(ListRoles::RELATIONS);
    }
}
