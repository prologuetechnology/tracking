<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\Permissions\ListPermissions;
use App\Actions\Permissions\ShowPermission;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionPageController extends Controller
{
    public function __construct(
        private readonly ListPermissions $listPermissions,
        private readonly ShowPermission $showPermission,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/permissions/Index', [
            'initialPermissions' => PermissionResource::collection(
                $this->listPermissions->execute(),
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
            'initialPermission' => PermissionResource::make(
                $this->showPermission->execute($permission),
            )->resolve(),
        ]);
    }
}
