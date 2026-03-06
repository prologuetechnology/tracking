<?php

namespace App\Http\Controllers\Api;

use App\Actions\Roles\CreateRole;
use App\Actions\Roles\ListRoles;
use App\Actions\Roles\ShowRole;
use App\Actions\Roles\SyncRolePermissions;
use App\Actions\Roles\UpdateRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRolePermissionsRequest;
use App\Http\Requests\DeleteRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function __construct(
        private readonly ListRoles $listRoles,
        private readonly CreateRole $createRole,
        private readonly ShowRole $showRole,
        private readonly UpdateRole $updateRole,
        private readonly SyncRolePermissions $syncRolePermissions,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            RoleResource::collection($this->listRoles->execute())->resolve(),
            Response::HTTP_OK,
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->createRole->execute($request->validated());

        return response()->json(
            RoleResource::make($role)->resolve(),
            Response::HTTP_CREATED,
        );
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json(
            RoleResource::make($this->showRole->execute($role))->resolve(),
            Response::HTTP_OK,
        );
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role = $this->updateRole->execute($role, $request->validated());

        return response()->json(
            RoleResource::make($role)->resolve(),
            Response::HTTP_OK,
        );
    }

    public function destroy(Role $role, DeleteRoleRequest $request): JsonResponse
    {
        $role->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function assignPermissions(AssignRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $role = $this->syncRolePermissions->execute(
            $role,
            $request->validated('permissions'),
        );

        return response()->json([
            'message' => 'Permissions assigned successfully.',
            'role' => RoleResource::make($role)->resolve(),
        ], Response::HTTP_OK);
    }
}
