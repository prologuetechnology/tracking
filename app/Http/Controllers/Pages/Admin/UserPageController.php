<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/users/Index', [
            'initialUsers' => UserResource::collection(
                User::query()->with(['roles.permissions'])->orderBy('email')->get(),
            )->resolve(),
            'allRoles' => RoleResource::collection(
                Role::query()->with('permissions')->orderBy('name')->get(),
            )->resolve(),
        ]);
    }
}
