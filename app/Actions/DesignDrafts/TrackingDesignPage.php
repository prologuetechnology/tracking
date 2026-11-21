<?php

namespace App\Actions\DesignDrafts;

use App\Actions\Tracking\ResolveTrackingPayload;
use App\Models\Company;
use App\Models\DesignDraftRevision;

class TrackingDesignPage
{
    public function __construct(private readonly ResolveTrackingPayload $resolver) {}

    public function preview(Company $company, DesignDraftRevision $revision, array $lookup): ?array
    {
        $payload = $this->resolver->execute(
            trackingNumber: $lookup['trackingNumber'],
            searchOption: $lookup['searchOption'],
            companyId: (string) $company->pipeline_company_id,
            brand: $company->brand,
            expectedCompany: $company,
            visibleSections: ClassicDesign::visibleSections($company, $revision->design),
        );

        return $payload['found'] ? self::fromPayload($revision, $payload) : null;
    }

    public static function fromPayload(DesignDraftRevision $revision, array $payload): array
    {
        $design = $revision->design;
        $company = $payload['company'];
        $visible = ClassicDesign::visibleSections($company, $design);
        $order = $design['section_orders'][$design['layout']] ?? null;
        $hidden = $design['hidden_modules'][$design['layout']] ?? [];
        $tracking = $payload['trackingData'];
        foreach (['special_instructions' => 'specialInstructions', 'customer_pos' => 'poNumbers'] as $slug => $field) {
            if (! in_array($slug, $visible, true)) {
                unset($tracking[$field]);
            }
        }

        return [
            'component' => DesignCatalog::LAYOUTS[$design['layout']]['component'],
            'props' => [
                'presentationVersion' => $design['presentation_version'] ?? 1,
                'sectionOrder' => $order === null ? null : array_values(array_diff($order, $hidden)),
                'headlineStyle' => $design['headline_style'] ?? 'layout',
                'brandImageUrls' => DesignBrandImages::urls($design),
                'emphasis' => $design['emphasis'] ?? 'delivery',
                'initialCompany' => [
                    'name' => $company->name,
                    'logo' => $design['logo'],
                    'banner' => $design['banner'],
                    'footer' => $design['footer'],
                    'theme' => $design['layout'] === ClassicDesign::LAYOUT ? $design['theme'] : ($design['palette_theme'] ?? $design['theme']),
                    'features' => array_values(array_map(fn ($slug) => ['slug' => $slug], $visible)),
                ],
                'initialTrackingData' => $tracking,
                'initialShipmentCoordinates' => in_array('enable_map', $visible, true) ? $payload['shipmentCoordinates'] : null,
                'initialShipmentDocuments' => in_array('enable_documents', $visible, true) ? $payload['shipmentDocuments'] : [],
            ],
        ];
    }
}
