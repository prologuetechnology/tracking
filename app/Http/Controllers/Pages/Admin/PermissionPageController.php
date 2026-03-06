<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/permissions/Index', [
            'permissions' => PermissionResource::collection(
                Permission::query()->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/permissions/Create');
    }

    public function show(Permission $permission): Response
    {
        return Inertia::render('admin/permissions/Edit', [
            'permissions' => PermissionResource::make($permission)->resolve(),
        ]);
    }
}
