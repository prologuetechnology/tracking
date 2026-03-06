<?php

namespace App\Http\Controllers\Api;

use App\Actions\Users\ListUsers;
use App\Actions\Users\ShowUser;
use App\Actions\Users\UpdateUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly ListUsers $listUsers,
        private readonly ShowUser $showUser,
        private readonly UpdateUserRole $updateUserRole,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            UserResource::collection($this->listUsers->execute())->resolve(),
            Response::HTTP_OK,
        );
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(
            UserResource::make($this->showUser->execute($user))->resolve(),
            Response::HTTP_OK,
        );
    }

    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user,
    ): JsonResponse {
        $roleName = $request->validated('role.name');

        return response()->json(
            UserResource::make(
                $this->updateUserRole->execute($user, $roleName),
            )->resolve(),
            Response::HTTP_OK,
        );
    }
}
