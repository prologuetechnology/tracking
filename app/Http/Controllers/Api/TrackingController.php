<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tracking\GetShipmentCoordinates;
use App\Actions\Tracking\ResolveTrackingCompany;
use App\Actions\Tracking\ResolveTrackingPayload;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetShipmentCoordinatesRequest;
use App\Http\Requests\GetTrackingStatusesRequest;
use App\Http\Resources\ShipmentCoordinateResource;
use App\Http\Resources\TrackingPayloadResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TrackingController extends Controller
{
    public function __construct(
        private readonly ResolveTrackingCompany $resolveTrackingCompany,
        private readonly GetShipmentCoordinates $getShipmentCoordinates,
        private readonly ResolveTrackingPayload $resolveTrackingPayload,
    ) {
    }

    public function trackingStatuses(GetTrackingStatusesRequest $request): JsonResponse
    {
        $payload = $this->resolveTrackingPayload->execute(
            trackingNumber: $request->validated('trackingNumber'),
            searchOption: $request->validated('searchOption'),
            companyId: $request->validated('companyId'),
        );

        if (! $payload['found']) {
            return response()->json([
                'error' => 'Tracking data not found or invalid.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json(
            TrackingPayloadResource::make($payload)->resolve(),
            Response::HTTP_OK,
        );
    }

    public function shipmentCoordinates(GetShipmentCoordinatesRequest $request): JsonResponse
    {
        $company = $this->resolveTrackingCompany->execute(
            pipelineCompanyId: (int) $request->validated('pipelineCompanyId'),
        );

        if (! $company || ! $company->hasFeature('enable_map')) {
            return response()->json([
                'error' => 'Map feature is disabled for this company.',
            ], Response::HTTP_FORBIDDEN);
        }

        $shipmentCoordinates = $this->getShipmentCoordinates->execute(
            company: $company,
            trackingNumber: $request->validated('trackingNumber'),
            pipelineCompanyId: $request->validated('pipelineCompanyId'),
        );

        if ($shipmentCoordinates === null) {
            return response()->json([
                'error' => 'Unable to fetch shipment coordinates.',
            ], Response::HTTP_BAD_GATEWAY);
        }

        return response()->json([
            'shipmentCoordinates' => ShipmentCoordinateResource::collection($shipmentCoordinates)->resolve(),
        ], Response::HTTP_OK);
    }
}
