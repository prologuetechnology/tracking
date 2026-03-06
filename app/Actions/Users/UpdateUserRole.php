<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdateUserRole
{
    public function execute(User $user, string $roleName): User
    {
        $user->syncRoles([$roleName]);

        return $user->load(ListUsers::RELATIONS);
    }
}
