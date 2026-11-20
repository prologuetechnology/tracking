<?php

namespace App\Actions\DesignDrafts;

use App\Actions\Tracking\ResolveTrackingPayload;
use App\Models\Company;
use App\Models\DesignDraftRevision;

class TrackingDesignPage
{
    public const LAYOUTS = [
        'classic.v1' => 'brandedTracking/Classic',
        'answer.v1' => 'brandedTracking/Answer',
    ];

    public function __construct(private readonly ResolveTrackingPayload $resolver) {}

    public function preview(Company $company, DesignDraftRevision $revision, array $lookup): ?array
    {
        $payload = $this->resolver->execute(
            trackingNumber: $lookup['trackingNumber'],
            searchOption: $lookup['searchOption'],
            companyId: (string) $company->pipeline_company_id,
            brand: $company->brand,
            expectedCompany: $company,
            visibleSections: $revision->design['sections'],
        );

        return $payload['found'] ? self::fromPayload($revision, $payload) : null;
    }

    public static function fromPayload(DesignDraftRevision $revision, array $payload): array
    {
        $design = $revision->design;
        $company = $payload['company'];
        $visible = array_intersect($design['sections'], ClassicDesign::availableSections($company));
        $tracking = $payload['trackingData'];
        foreach (['special_instructions' => 'specialInstructions', 'customer_pos' => 'poNumbers'] as $slug => $field) {
            if (! in_array($slug, $visible, true)) {
                unset($tracking[$field]);
            }
        }

        return [
            'component' => self::LAYOUTS[$design['layout']],
            'props' => [
                'brandImageUrls' => DesignBrandImages::urls($design),
                'emphasis' => $design['emphasis'] ?? 'delivery',
                'initialCompany' => [
                    'name' => $company->name,
                    'logo' => $design['logo'],
                    'banner' => $design['banner'],
                    'footer' => $design['footer'],
                    'theme' => $design['theme'],
                    'features' => array_values(array_map(fn ($slug) => ['slug' => $slug], $visible)),
                ],
                'initialTrackingData' => $tracking,
                'initialShipmentCoordinates' => $payload['shipmentCoordinates'],
                'initialShipmentDocuments' => $payload['shipmentDocuments'],
            ],
        ];
    }
}
