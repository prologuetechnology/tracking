<?php

namespace App\Support\Testing;

class FakePipelinePayloads
{
    public const MULTIDAY_REFERENCE = 'CURATED-MULTIDAY';

    public static function shipmentSearch(string $trackingNumber, int $companyId = 1001): array
    {
        $payload = [
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

        if ($trackingNumber === self::MULTIDAY_REFERENCE) {
            $payload['data'][0]['estimatedDeliveryDate'] = null;
            $payload['data'][0]['allStatuses'] = [
                ['id' => 15, 'current_status' => 'At terminal', 'status_date_time' => '2026-03-04T16:00:00Z', 'current_location' => 'Dallas, TX'],
                ['id' => 22, 'current_status' => 'Arrived at hub', 'status_date_time' => '2026-03-05T15:00:00Z', 'current_location' => 'Memphis, TN'],
                ['id' => 22, 'current_status' => 'Delivery exception', 'status_date_time' => '2026-03-05T17:00:00Z', 'current_location' => 'Memphis, TN'],
                ['id' => 35, 'current_status' => 'In transit', 'status_date_time' => null, 'timestamp' => '2026-03-06T16:00:00Z', 'current_location' => 'Chicago, IL', 'pro_number' => 'PRO-CARRIER-SCAN'],
            ];
        }

        if ($trackingNumber === 'SIGNAL-EMPTY') {
            $payload['data'][0]['allStatuses'] = [];
        }
        if ($trackingNumber === 'SIGNAL-SCANS') {
            $payload['data'][0]['allStatuses'] = [
                ['current_status' => 'Created'],
                ['current_status' => 'Picked up', 'status_date_time' => '2026-03-01T09:00:00Z', 'current_location' => 'Chicago, IL'],
                ['current_status' => 'Delivery exception', 'timestamp' => '2026-03-02T09:00:00Z', 'current_location' => 'Memphis, TN'],
                ['current_status' => 'Delivered', 'status_date_time' => '2026-03-03T09:00:00Z', 'current_location' => 'Chicago, IL'],
            ];
        }

        return $payload;
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
