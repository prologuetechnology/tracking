<?php

namespace App\Http\Controllers\Api;

use App\Actions\Permissions\CreatePermission;
use App\Actions\Permissions\ListPermissions;
use App\Actions\Permissions\ShowPermission;
use App\Actions\Permissions\UpdatePermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeletePermissionRequest;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function __construct(
        private readonly ListPermissions $listPermissions,
        private readonly CreatePermission $createPermission,
        private readonly ShowPermission $showPermission,
        private readonly UpdatePermission $updatePermission,
    ) {
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            PermissionResource::collection($this->listPermissions->execute())->resolve(),
            Response::HTTP_OK,
        );
    }

    public function store(StorePermissionRequest $request): \Illuminate\Http\JsonResponse
    {
        $permission = $this->createPermission->execute($request->validated());

        return response()->json(
            PermissionResource::make($permission)->resolve(),
            Response::HTTP_CREATED,
        );
    }

    public function show(Permission $permission): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            PermissionResource::make($this->showPermission->execute($permission))->resolve(),
            Response::HTTP_OK,
        );
    }

    public function update(
        UpdatePermissionRequest $request,
        Permission $permission,
    ): \Illuminate\Http\JsonResponse {
        $permission = $this->updatePermission->execute(
            $permission,
            $request->validated(),
        );

        return response()->json(
            PermissionResource::make($permission)->resolve(),
            Response::HTTP_OK,
        );
    }

    public function destroy(
        Permission $permission,
        DeletePermissionRequest $request,
    ): \Illuminate\Http\JsonResponse {
        $permission->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
