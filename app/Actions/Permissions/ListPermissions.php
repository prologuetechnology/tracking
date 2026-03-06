<?php

namespace App\Actions\Permissions;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class ListPermissions
{
    public function execute(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get();
    }
}
