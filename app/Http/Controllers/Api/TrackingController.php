<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tracking\ResolveTrackingPayload;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetShipmentCoordinatesRequest;
use App\Http\Requests\GetTrackingStatusesRequest;
use App\Models\Company;
use App\Services\Pipeline\PipelineApiShipmentCoordinates;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TrackingController extends Controller
{
    public function __construct(
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

        return response()->json([
            'trackingData' => $payload['trackingData'],
            'company' => $payload['company'],
            'shipmentCoordinates' => $payload['shipmentCoordinates'],
            'shipmentDocuments' => $payload['shipmentDocuments'],
        ], Response::HTTP_OK);
    }

    public function shipmentCoordinates(GetShipmentCoordinatesRequest $request): JsonResponse
    {
        $shipmentCoordinates = new PipelineApiShipmentCoordinates;

        $response = $shipmentCoordinates->getCoordinates(
            $request->validated('trackingNumber'),
            $request->validated('pipelineCompanyId')
        );

        $company = Company::findByIdentifier(
            null,
            null,
            $request->validated('pipelineCompanyId'),
        );

        if (! $company || ! $company->hasFeature('enable_map')) {
            return response()->json([
                'error' => 'Map feature is disabled for this company.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json($response->json(), Response::HTTP_OK);
    }
}
