<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeletePermissionRequest;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            PermissionResource::collection(Permission::query()->orderBy('name')->get()),
            Response::HTTP_OK,
        );
    }

    public function store(StorePermissionRequest $request): \Illuminate\Http\JsonResponse
    {
        $permission = Permission::create($request->validated());

        return response()->json(PermissionResource::make($permission), Response::HTTP_CREATED);
    }

    public function show(Permission $permission): \Illuminate\Http\JsonResponse
    {
        return response()->json(PermissionResource::make($permission), Response::HTTP_OK);
    }

    public function update(
        UpdatePermissionRequest $request,
        Permission $permission,
    ): \Illuminate\Http\JsonResponse {
        $permission->update($request->validated());

        return response()->json(PermissionResource::make($permission), Response::HTTP_OK);
    }

    public function destroy(
        Permission $permission,
        DeletePermissionRequest $request,
    ): \Illuminate\Http\JsonResponse {
        $permission->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
