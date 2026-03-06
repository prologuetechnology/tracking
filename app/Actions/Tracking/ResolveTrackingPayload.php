<?php

namespace App\Actions\Tracking;

use App\Models\Company;
use App\Services\Pipeline\PipelineApiShipmentCoordinates;
use App\Services\Pipeline\PipelineApiShipmentSearch;
use Illuminate\Http\Client\Response;

class ResolveTrackingPayload
{
    public function __construct(
        private readonly GetShipmentDocuments $getShipmentDocuments,
    ) {
    }

    public function execute(
        string $trackingNumber,
        string $searchOption,
        ?string $companyId = null,
        ?string $brand = null,
    ): array {
        $shipmentSearchResponse = $this->search(
            trackingNumber: $trackingNumber,
            searchOption: $searchOption,
        );

        if ($this->isMissing($shipmentSearchResponse)) {
            return [
                'found' => false,
            ];
        }

        $pipelineCompanyId = $shipmentSearchResponse->object()->data[0]?->companyId;
        $company = Company::findByIdentifier($brand, $companyId, $pipelineCompanyId);

        if ($company?->requires_brand && ! $brand) {
            return [
                'found' => false,
            ];
        }

        $trackingData = $shipmentSearchResponse->json();

        return [
            'found' => true,
            'trackingData' => $trackingData,
            'company' => $company?->loadMissing(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']),
            'shipmentCoordinates' => $this->coordinates(
                company: $company,
                trackingNumber: $trackingNumber,
                pipelineCompanyId: $pipelineCompanyId,
            ),
            'shipmentDocuments' => $this->getShipmentDocuments->execute(
                company: $company,
                trackingNumber: $shipmentSearchResponse->object()->data[0]?->bolNum,
            ),
        ];
    }

    private function search(string $trackingNumber, string $searchOption): Response
    {
        $shipmentSearchClient = new PipelineApiShipmentSearch;

        return $shipmentSearchClient->searchShipment(
            trackingNumber: $trackingNumber,
            searchOption: $searchOption,
            globalSearch: true,
        );
    }

    private function isMissing(Response $response): bool
    {
        return $response->failed()
            || $response->clientError()
            || empty($response->object()->data);
    }

    private function coordinates(
        ?Company $company,
        string $trackingNumber,
        ?string $pipelineCompanyId,
    ): ?array {
        if (! $company?->hasFeature('enable_map')) {
            return null;
        }

        $shipmentCoordinatesResponse = (new PipelineApiShipmentCoordinates)->getCoordinates(
            $trackingNumber,
            $pipelineCompanyId,
        );

        return $shipmentCoordinatesResponse->json();
    }
}
