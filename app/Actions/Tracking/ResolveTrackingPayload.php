<?php

namespace App\Actions\Tracking;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResolveTrackingPayload
{
    public function __construct(
        private readonly SearchShipment $searchShipment,
        private readonly ResolveTrackingCompany $resolveTrackingCompany,
        private readonly GetShipmentCoordinates $getShipmentCoordinates,
        private readonly GetShipmentDocuments $getShipmentDocuments,
    ) {
    }

    public function execute(
        string $trackingNumber,
        string $searchOption,
        ?string $companyId = null,
        ?string $brand = null,
    ): array {
        try {
            $shipmentSearchResponse = $this->search(
                trackingNumber: $trackingNumber,
                searchOption: $searchOption,
            );

            if ($this->isMissing($shipmentSearchResponse)) {
                $this->logSearchFailureIfNeeded(
                    response: $shipmentSearchResponse,
                    trackingNumber: $trackingNumber,
                    searchOption: $searchOption,
                    companyId: $companyId,
                    brand: $brand,
                );

                return [
                    'found' => false,
                ];
            }

            $trackingData = $this->extractShipment($shipmentSearchResponse);
            $pipelineCompanyId = data_get($trackingData, 'companyId');
            $resolvedCompanyId = is_numeric($companyId) ? (int) $companyId : null;
            $resolvedPipelineCompanyId = is_numeric($pipelineCompanyId) ? (int) $pipelineCompanyId : null;

            $company = $this->resolveTrackingCompany->execute(
                brand: $brand,
                companyId: $resolvedCompanyId,
                pipelineCompanyId: $resolvedPipelineCompanyId,
            );

            $company?->loadMissing([
                'apiToken',
                'banner.imageType',
                'features',
                'footer.imageType',
                'logo.imageType',
                'theme',
            ]);

            if ($company?->requires_brand && ! $brand) {
                return [
                    'found' => false,
                ];
            }

            return [
                'found' => true,
                'trackingData' => $trackingData,
                'company' => $company,
                'shipmentCoordinates' => $this->getShipmentCoordinates->execute(
                    company: $company,
                    trackingNumber: data_get($trackingData, 'bolNum', $trackingNumber),
                    pipelineCompanyId: is_scalar($pipelineCompanyId) ? (string) $pipelineCompanyId : null,
                ),
                'shipmentDocuments' => $this->getShipmentDocuments->execute(
                    company: $company,
                    trackingNumber: data_get($trackingData, 'bolNum', $trackingNumber),
                ),
            ];
        } catch (Throwable $exception) {
            Log::warning('Tracking payload resolution failed.', [
                'status' => null,
                'exception' => $exception::class,
                'company_id' => $companyId,
                'brand' => $brand,
                'search_option' => $searchOption,
                'tracking_number_suffix' => $this->maskTrackingNumber($trackingNumber),
            ]);

            return [
                'found' => false,
            ];
        }
    }

    private function search(string $trackingNumber, string $searchOption): Response
    {
        return $this->searchShipment->execute($trackingNumber, $searchOption);
    }

    private function isMissing(Response $response): bool
    {
        return $response->failed()
            || $response->clientError()
            || empty($this->extractShipment($response));
    }

    private function extractShipment(Response $response): ?array
    {
        $shipment = data_get($response->json(), 'data.0');

        return is_array($shipment) ? $shipment : null;
    }

    private function logSearchFailureIfNeeded(
        Response $response,
        string $trackingNumber,
        string $searchOption,
        ?string $companyId,
        ?string $brand,
    ): void {
        if (! $response->failed() && ! $response->clientError()) {
            return;
        }

        Log::warning('Shipment search failed.', [
            'status' => $response->status(),
            'exception' => null,
            'company_id' => $companyId,
            'brand' => $brand,
            'search_option' => $searchOption,
            'tracking_number_suffix' => $this->maskTrackingNumber($trackingNumber),
        ]);
    }

    private function maskTrackingNumber(string $trackingNumber): string
    {
        $suffix = substr($trackingNumber, -4);
        $prefixLength = max(strlen($trackingNumber) - strlen($suffix), 0);

        return str_repeat('*', $prefixLength).$suffix;
    }
}
