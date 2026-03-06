<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\Roles\ListRoles;
use App\Actions\Users\ListUsers;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use Inertia\Inertia;
use Inertia\Response;

class UserPageController extends Controller
{
    public function __construct(
        private readonly ListUsers $listUsers,
        private readonly ListRoles $listRoles,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/users/Index', [
            'initialUsers' => UserResource::collection(
                $this->listUsers->execute(),
            )->resolve(),
            'initialRoles' => RoleResource::collection(
                $this->listRoles->execute(),
            )->resolve(),
        ]);
    }
}
