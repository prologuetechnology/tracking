<?php

namespace App\Support\Testing;

class FakePipelinePayloads
{
    public static function shipmentSearch(string $trackingNumber, int $companyId = 1001): array
    {
        return [
            'data' => [
                [
                    'id' => 1,
                    'companyId' => $companyId,
                    'bolNum' => $trackingNumber,
                    'carrierPro' => 'PRO-'.$trackingNumber,
                    'carrierName' => 'Acme Carrier',
                    'allStatuses' => [
                        [
                            'id' => 10,
                            'current_status' => 'Delivered',
                            'status_date_time' => '2026-03-06T10:00:00Z',
                            'current_location' => 'Chicago, IL',
                            'pro_number' => 'PRO-'.$trackingNumber,
                        ],
                    ],
                    'lineItems' => [
                        ['pieces' => 2],
                    ],
                    'totalWeight' => 1200,
                    'originLocation' => [
                        'name' => 'Origin Hub',
                        'address' => '1 Start St',
                        'city' => 'Dallas',
                        'state' => 'TX',
                        'zipCode' => '75001',
                    ],
                    'destinationLocation' => [
                        'name' => 'Destination Hub',
                        'address' => '9 End Ave',
                        'city' => 'Chicago',
                        'state' => 'IL',
                        'zipCode' => '60601',
                    ],
                    'estimatedDeliveryDate' => '2026-03-06',
                    'estimatedPickupDate' => '2026-03-01',
                    'specialInstructions' => 'Leave at dock door 3.',
                    'poNumbers' => ['PO-1'],
                ],
            ],
        ];
    }

    public static function shipmentCoordinates(): array
    {
        return [
            [
                'lastLocation' => [
                    'coordinates' => [
                        'lat' => 41.88,
                        'lng' => -90.5,
                    ],
                ],
                'allKnownLocations' => [
                    [
                        'coordinates' => [
                            'lat' => 41.88,
                            'lng' => -90.5,
                        ],
                    ],
                    [
                        'coordinates' => [
                            'lat' => 41.9,
                            'lng' => -90.45,
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function shipmentDocuments(string $appUrl, string $trackingNumber): array
    {
        $base = rtrim($appUrl, '/').'/testing/fake-pipeline/documents';

        return [
            [
                'name' => 'bol',
                'file' => "{$base}/{$trackingNumber}-bol.pdf",
            ],
            [
                'name' => 'pod',
                'file' => "{$base}/{$trackingNumber}-pod.pdf",
            ],
        ];
    }
}
