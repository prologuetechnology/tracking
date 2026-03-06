<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/role/Index', [
            'roles' => RoleResource::collection(
                Role::query()->with('permissions')->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    public function show(Role $role): Response
    {
        $role->load('permissions');

        return Inertia::render('admin/role/Edit', [
            'initialRole' => RoleResource::make($role)->resolve(),
            'allPermissions' => PermissionResource::collection(
                Permission::query()->orderBy('name')->get(),
            )->resolve(),
        ]);
    }
}
