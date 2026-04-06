<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListUsers
{
    public const RELATIONS = ['roles.permissions', 'permissions'];

    public function execute(): Collection
    {
        return User::query()
            ->with(self::RELATIONS)
            ->orderBy('email')
            ->get();
    }
}
