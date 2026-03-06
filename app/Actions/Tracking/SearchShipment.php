<?php

namespace App\Actions\Tracking;

use App\Services\Pipeline\PipelineApiShipmentSearch;
use Illuminate\Http\Client\Response;

class SearchShipment
{
    public function __construct(
        private readonly PipelineApiShipmentSearch $shipmentSearchClient,
    ) {
    }

    public function execute(string $trackingNumber, string $searchOption): Response
    {
        return $this->shipmentSearchClient->searchShipment(
            trackingNumber: $trackingNumber,
            searchOption: $searchOption,
            globalSearch: true,
        );
    }
}
