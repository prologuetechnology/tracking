<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\Permissions\ListPermissions;
use App\Actions\Roles\ListRoles;
use App\Actions\Roles\ShowRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RolePageController extends Controller
{
    public function __construct(
        private readonly ListRoles $listRoles,
        private readonly ShowRole $showRole,
        private readonly ListPermissions $listPermissions,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/role/Index', [
            'initialRoles' => RoleResource::collection(
                $this->listRoles->execute(),
            )->resolve(),
        ]);
    }

    public function show(Role $role): Response
    {
        return Inertia::render('admin/role/Edit', [
            'initialRole' => RoleResource::make(
                $this->showRole->execute($role),
            )->resolve(),
            'initialPermissions' => PermissionResource::collection(
                $this->listPermissions->execute(),
            )->resolve(),
        ]);
    }
}
