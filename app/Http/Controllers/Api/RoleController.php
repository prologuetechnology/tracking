<?php

namespace App\Http\Controllers\Api;

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
    public function index(): JsonResponse
    {
        return response()->json(
            RoleResource::collection(Role::query()->with('permissions')->orderBy('name')->get()),
            Response::HTTP_OK,
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = Role::create(['name' => $validated['name']]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json(
            RoleResource::make($role->load('permissions')),
            Response::HTTP_CREATED,
        );
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json(
            RoleResource::make($role->load('permissions')),
            Response::HTTP_OK,
        );
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $validated = $request->validated();

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json(
            RoleResource::make($role->load('permissions')),
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
        $validated = $request->validated();

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions assigned successfully.',
            'role' => RoleResource::make($role->load('permissions')),
        ], Response::HTTP_OK);
    }
}
