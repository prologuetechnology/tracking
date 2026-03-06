<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tracking\GetShipmentDocuments;
use App\Actions\Tracking\ResolveTrackingCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetShipmentDocumentsRequest;
use App\Http\Resources\ShipmentDocumentResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(
        private readonly ResolveTrackingCompany $resolveTrackingCompany,
        private readonly GetShipmentDocuments $getShipmentDocuments,
    ) {
    }

    public function shipmentDocuments(GetShipmentDocumentsRequest $request): JsonResponse
    {
        $company = $this->resolveTrackingCompany->execute(
            companyId: (int) $request->validated('companyId'),
        );

        $documents = $this->getShipmentDocuments->execute(
            $company,
            $request->validated('trackingNumber'),
        );

        return response()->json([
            'shipmentDocuments' => ShipmentDocumentResource::collection($documents)->resolve(),
        ], Response::HTTP_OK);
    }
}
