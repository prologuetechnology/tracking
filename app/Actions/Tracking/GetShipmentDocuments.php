<?php

namespace App\Actions\Tracking;

use App\Actions\GetShipmentDocumentsWithMetadata;
use App\Models\Company;
use App\Services\Pipeline\PipelineApiShipmentDocuments;
use Illuminate\Support\Facades\Log;

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

        if ($shipmentDocumentsResponse->failed()) {
            Log::warning('Shipment documents lookup failed.', [
                'status' => $shipmentDocumentsResponse->status(),
                'company_id' => $company->id,
                'pipeline_company_id' => $company->pipeline_company_id,
                'tracking_number_suffix' => $this->maskTrackingNumber($trackingNumber),
            ]);

            return [];
        }

        return (new GetShipmentDocumentsWithMetadata)(
            $shipmentDocumentsResponse->json(),
            ['bol', 'pod'],
        );
    }

    private function maskTrackingNumber(string $trackingNumber): string
    {
        $suffix = substr($trackingNumber, -4);
        $prefixLength = max(strlen($trackingNumber) - strlen($suffix), 0);

        return str_repeat('*', $prefixLength).$suffix;
    }
}
