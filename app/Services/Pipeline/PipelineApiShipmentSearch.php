<?php

namespace App\Services\Pipeline;

use App\Support\Testing\FakePipelineResponses;
use Illuminate\Http\Client\Response;

class PipelineApiShipmentSearch extends PipelineApiBaseService
{
    protected $endpoint;

    protected $apiToken;

    public function __construct(?string $apiToken = null)
    {
        if (empty($apiToken)) {
            parent::__construct();
        }

        if (! empty($apiToken)) {
            parent::__construct(apiKey: $apiToken);
        }

        $this->endpoint = '/shipmentSearch';
    }

    public function searchShipment(
        ?string $trackingNumber = '',
        ?string $searchOption = '',
        ?bool $globalSearch = false,
    ): Response {
        if (app()->environment('dusk.local')) {
            return FakePipelineResponses::shipmentSearch(
                $trackingNumber ?? '',
            );
        }

        $data = [
            'trackNum' => $trackingNumber,
            'searchOption' => $searchOption,
            'globalSearch' => $globalSearch,
        ];

        $response = $this->makeRequest('POST', $this->endpoint, $data);

        return $response;
    }
}
