<?php

namespace App\Actions\Tracking;

use App\Actions\GetShipmentDocumentsWithMetadata;
use App\Models\Company;
use App\Services\Pipeline\PipelineApiShipmentDocuments;

class GetShipmentDocuments
{
    public function execute(?Company $company, string $trackingNumber): array
    {
        if (! $company?->apiToken()->exists() || ! $company->hasFeature('enable_documents')) {
            return [];
        }

        $shipmentDocumentsClient = new PipelineApiShipmentDocuments(
            apiKey: $company->apiToken->api_token,
        );

        $shipmentDocumentsResponse = $shipmentDocumentsClient->getShipmentDocuments(
            $trackingNumber,
        );

        return (new GetShipmentDocumentsWithMetadata)(
            $shipmentDocumentsResponse->json(),
            ['bol', 'pod'],
        );
    }
}
