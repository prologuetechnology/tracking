<?php

namespace App\Http\Controllers\Api;

use App\Actions\AllowedDomains\CreateAllowedDomain;
use App\Actions\AllowedDomains\ListAllowedDomains;
use App\Actions\AllowedDomains\ToggleAllowedDomainStatus;
use App\Actions\AllowedDomains\UpdateAllowedDomain;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAllowedDomainRequest;
use App\Http\Requests\StoreAllowedDomainRequest;
use App\Http\Requests\ToggleAllowedDomainStatusRequest;
use App\Http\Requests\UpdateAllowedDomainRequest;
use App\Http\Resources\AllowedDomainResource;
use App\Models\AllowedDomain;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AllowedDomainController extends Controller
{
    public function __construct(
        private readonly CreateAllowedDomain $createAllowedDomain,
        private readonly ListAllowedDomains $listAllowedDomains,
        private readonly ToggleAllowedDomainStatus $toggleAllowedDomainStatus,
        private readonly UpdateAllowedDomain $updateAllowedDomain,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            AllowedDomainResource::collection($this->listAllowedDomains->execute()),
            Response::HTTP_OK,
        );
    }

    public function store(StoreAllowedDomainRequest $request): JsonResponse
    {
        $allowedDomain = $this->createAllowedDomain->execute(
            $request->validated(),
            $request->user(),
        );

        return response()->json(
            AllowedDomainResource::make($allowedDomain),
            Response::HTTP_CREATED,
        );
    }

    public function show(AllowedDomain $allowedDomain): JsonResponse
    {
        return response()->json(
            AllowedDomainResource::make($allowedDomain),
            Response::HTTP_OK,
        );
    }

    public function update(
        UpdateAllowedDomainRequest $request,
        AllowedDomain $allowedDomain,
    ): JsonResponse {
        $allowedDomain = $this->updateAllowedDomain->execute(
            $allowedDomain,
            $request->validated(),
            $request->user(),
        );

        return response()->json(
            AllowedDomainResource::make($allowedDomain),
            Response::HTTP_OK,
        );
    }

    public function toggleActiveStatus(
        ToggleAllowedDomainStatusRequest $request,
        AllowedDomain $allowedDomain,
    ): JsonResponse {
        $allowedDomain = $this->toggleAllowedDomainStatus->execute($allowedDomain);

        return response()->json(
            AllowedDomainResource::make($allowedDomain),
            Response::HTTP_OK,
        );
    }

    public function destroy(
        DeleteAllowedDomainRequest $request,
        AllowedDomain $allowedDomain,
    ): JsonResponse {
        $allowedDomain->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
