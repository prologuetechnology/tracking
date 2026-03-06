<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            UserResource::collection(
                User::query()->with(['roles.permissions'])->orderBy('email')->get(),
            ),
            Response::HTTP_OK,
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['roles', 'roles.permissions', 'permissions']);

        return response()->json(UserResource::make($user), Response::HTTP_OK);
    }

    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user,
    ): JsonResponse {
        $roleName = $request->validated('role.name');

        $user->syncRoles([$roleName]);
        $user->load(['roles.permissions', 'permissions']);

        return response()->json(UserResource::make($user), Response::HTTP_OK);
    }
}
