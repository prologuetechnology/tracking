<?php

namespace App\Actions\Roles;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class ListRoles
{
    public const RELATIONS = ['permissions'];

    public function execute(): Collection
    {
        return Role::query()
            ->with(self::RELATIONS)
            ->orderBy('name')
            ->get();
    }
}
