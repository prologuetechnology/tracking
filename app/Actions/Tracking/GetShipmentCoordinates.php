<?php

namespace App\Actions\Tracking;

use App\Models\Company;
use App\Services\Pipeline\PipelineApiShipmentCoordinates;
use Illuminate\Support\Facades\Log;

class GetShipmentCoordinates
{
    public function __construct(
        private readonly PipelineApiShipmentCoordinates $shipmentCoordinatesClient,
    ) {
    }

    public function execute(
        ?Company $company,
        string $trackingNumber,
        ?string $pipelineCompanyId,
    ): ?array {
        if (! $company?->hasFeature('enable_map')) {
            return null;
        }

        $response = $this->shipmentCoordinatesClient->getCoordinates(
            $trackingNumber,
            $pipelineCompanyId,
        );

        if ($response->failed()) {
            Log::warning('Shipment coordinates lookup failed.', [
                'status' => $response->status(),
                'company_id' => $company?->id,
                'pipeline_company_id' => $pipelineCompanyId,
                'tracking_number_suffix' => $this->maskTrackingNumber($trackingNumber),
            ]);

            return null;
        }

        return collect($response->json())
            ->filter(fn (mixed $coordinate): bool => is_array($coordinate))
            ->values()
            ->all();
    }

    private function maskTrackingNumber(string $trackingNumber): string
    {
        $suffix = substr($trackingNumber, -4);
        $prefixLength = max(strlen($trackingNumber) - strlen($suffix), 0);

        return str_repeat('*', $prefixLength).$suffix;
    }
}
