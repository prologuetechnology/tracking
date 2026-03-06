<?php

namespace App\Actions\Users;

use App\Models\User;

class ShowUser
{
    public function execute(User $user): User
    {
        return $user->load(ListUsers::RELATIONS);
    }
}
