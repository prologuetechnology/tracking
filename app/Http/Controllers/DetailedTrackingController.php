<?php

namespace App\Http\Controllers;

use App\Actions\GetShipmentDocumentsWithMetadata;
use App\Models\Company;
use App\Services\Pipeline\PipelineApiShipmentCoordinates;
use App\Services\Pipeline\PipelineApiShipmentDocuments;
use App\Services\Pipeline\PipelineApiShipmentSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DetailedTrackingController extends Controller
{
    public function index(Request $request)
    {
        $trackingNumber = $request->query('trackingNumber') ?? null;
        $searchOption = $request->query('searchOption') ?? null;
        $companyId = $request->query('companyId') ?? null;
        $brand = $request->query('brand') ? strtoupper($request->query('brand')) : null;

        $shipmentSearchClient = new PipelineApiShipmentSearch;

        $shipmentSearchResponse = $shipmentSearchClient->searchShipment(
            trackingNumber: $trackingNumber,
            searchOption: $searchOption,
            globalSearch: true,
        );

        // If error in trackingDataResponse, redirect to error page.
        if ($shipmentSearchResponse->failed() || $shipmentSearchResponse->clientError() || empty($shipmentSearchResponse->object()->data)) {
            return redirect(route('trackShipment.notFound', $trackingNumber));
        }

        // Pipeline ID of the company the shipment belongs to.
        $pipelineCompanyId = $shipmentSearchResponse->object()->data[0]?->companyId;

        $trackingData = $shipmentSearchResponse->json();

        // Attempt to get local company model from either the slug or the Pipeline company ID.
        $company = Company::findByIdentifier($brand, $companyId, $pipelineCompanyId);

        // If company requires brand and brand is not provided, redirect to error page.
        if ($company?->requires_brand && ! $brand) {
            return redirect(route('trackShipment.notFound', $trackingNumber));
        }

        $shipmentCoordinates = null;

        // Get shipment coordinate data if enable_map option is active.
        if ($company?->hasFeature('enable_map')) {
            $pipelineApiShipmentCoordinates = new PipelineApiShipmentCoordinates;

            $shipmentCoordinatesResponse = $pipelineApiShipmentCoordinates->getCoordinates(
                $trackingNumber,
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

        return Inertia::render('brandedTracking/Index', [
            'initialTrackingData' => $trackingData,
            'initialCompany' => $company,
            'initialShipmentCoordinates' => $shipmentCoordinates,
            'initialShipmentDocuments' => $selectedDocuments ?? [],
        ]);
    }

    public function trackingDataNotFound($trackingNumber)
    {
        return Inertia::render('brandedTracking/Error', [
            'trackingNumber' => $trackingNumber,
        ]);
    }
}
