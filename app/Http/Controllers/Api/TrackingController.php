<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetShipmentDocumentsWithMetadata;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Pipeline\PipelineApiShipmentCoordinates;
use App\Services\Pipeline\PipelineApiShipmentDocuments;
use App\Services\Pipeline\PipelineApiShipmentSearch;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TrackingController extends Controller
{
    public function trackingStatuses(Request $request): JsonResponse
    {
        $shipmentSearchClient = new PipelineApiShipmentSearch;

        $shipmentSearchResponse = $shipmentSearchClient->searchShipment(
            $request->input('trackingNumber'),
            $request->input('searchOption'),
            globalSearch: true,
        );

        // If error in trackingDataResponse, redirect to error page.
        if ($shipmentSearchResponse->failed() || $shipmentSearchResponse->clientError() || empty($shipmentSearchResponse->object()->data)) {
            return response()->json([
                'error' => 'Tracking data not found or invalid.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Pipeline ID of the company the shipment belongs to.
        $pipelineCompanyId = $shipmentSearchResponse->object()->data[0]?->companyId;

        $trackingData = $shipmentSearchResponse->json();

        // Attempt to get local company model from either the slug or the Pipeline company ID.
        $company = Company::findByIdentifier(null, $request->input('companyId'), $pipelineCompanyId);

        $shipmentCoordinates = null;

        // Get shipment coordinate data if enable_map option is active.
        if ($company?->hasFeature('enable_map')) {
            $pipelineApiShipmentCoordinates = new PipelineApiShipmentCoordinates;

            $shipmentCoordinatesResponse = $pipelineApiShipmentCoordinates->getCoordinates(
                $request->input('trackingNumber'),
                $pipelineCompanyId
            );

            $shipmentCoordinatesResponse->json();

            $shipmentCoordinates = $shipmentCoordinatesResponse->json();
        }

        if ($company?->apiToken()->exists() && $company->hasFeature('enable_documents')) {
            $selectedDocuments = [];

            $shipmentDocumentsClient = new PipelineApiShipmentDocuments(apiKey: $company?->apiToken?->api_token);

            $shipmentDocumentsResponse = $shipmentDocumentsClient->getShipmentDocuments(
                $shipmentSearchResponse->object()->data[0]?->bolNum
            );

            $selectedDocuments = (new GetShipmentDocumentsWithMetadata)(
                $shipmentDocumentsResponse->json(),
                ['bol', 'pod']
            );
        }

        return response()->json([
            'trackingData' => $trackingData,
            'company' => $company,
            'shipmentCoordinates' => $shipmentCoordinates,
            'shipmentDocuments' => $selectedDocuments ?? [],
        ], Response::HTTP_OK);
    }

    public function shipmentCoordinates(Request $request): JsonResponse
    {
        $shipmentCoordinates = new PipelineApiShipmentCoordinates;

        $response = $shipmentCoordinates->getCoordinates(
            $request->input('trackingNumber'),
            $request->input('pipelineCompanyId')
        );

        $company = Company::findByIdentifier(null, null, $request->input('pipelineCompanyId'));

        if (! $company || ! $company->hasFeature('enable_map')) {
            return response()->json([
                'error' => 'Map feature is disabled for this company.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json($response->json(), Response::HTTP_OK);
    }
}
