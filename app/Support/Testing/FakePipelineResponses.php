<?php

namespace App\Support\Testing;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

class FakePipelineResponses
{
    public static function shipmentSearch(string $trackingNumber, int $companyId = 1001): Response
    {
        if (in_array($trackingNumber, ['BOL404', 'NOTFOUND'], true)) {
            return self::json(['data' => []]);
        }

        return self::json(FakePipelinePayloads::shipmentSearch($trackingNumber, $companyId));
    }

    public static function shipmentCoordinates(): Response
    {
        return self::json(FakePipelinePayloads::shipmentCoordinates());
    }

    public static function shipmentDocuments(string $appUrl, string $trackingNumber): Response
    {
        return self::json(FakePipelinePayloads::shipmentDocuments($appUrl, $trackingNumber));
    }

    private static function json(array $payload, int $status = 200): Response
    {
        return new Response(
            new Psr7Response(
                $status,
                ['Content-Type' => 'application/json'],
                json_encode($payload, JSON_THROW_ON_ERROR),
            ),
        );
    }
}
