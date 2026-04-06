<?php

namespace App\Services\Pipeline;

use App\Support\Testing\FakePipelineResponses;
use Illuminate\Http\Client\Response;

class PipelineApiShipmentCoordinates extends PipelineApiBaseService
{
    protected $endpoint;

    public function __construct()
    {
        parent::__construct(config('services.pipeline.base_url').'/app.php?r=mapApi');

        $this->endpoint = '/getRoutes';
    }

    /**
     * Get shipment coordinates for Trimble tracking map.
     */
    public function getCoordinates(
        ?string $trackingNumber = '',
        ?string $pipelineCompanyId = '',
    ): Response {
        if (app()->environment('dusk.local')) {
            return FakePipelineResponses::shipmentCoordinates();
        }

        $response = $this->makeRequest('POST', $this->endpoint.'/getRoutes&Filter[bolNum]='.$trackingNumber.'&Filter[companyId]='.$pipelineCompanyId);

        return $response;
    }
}
