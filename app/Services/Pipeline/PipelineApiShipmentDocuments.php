<?php

namespace App\Services\Pipeline;

use App\Support\Testing\FakePipelineResponses;
use Illuminate\Http\Client\Response;

class PipelineApiShipmentDocuments extends PipelineApiBaseService
{
    protected $endpoint;

    public function __construct(string $apiKey)
    {
        parent::__construct(apiKey: $apiKey);

        $this->endpoint = '/Execute/GetDocuments';
    }

    public function getShipmentDocuments(string $trackingNumber): Response
    {
        if (app()->environment('dusk.local')) {
            return FakePipelineResponses::shipmentDocuments(
                rtrim((string) config('app.url'), '/'),
                $trackingNumber,
            );
        }

        $data = [
            'RequestOptions' => [
                'testMode' => false,
            ],
            'Request' => [
                'BOLNumber' => $trackingNumber,
            ],
        ];

        return $this->makeRequest('POST', $this->endpoint, $data);
    }
}
