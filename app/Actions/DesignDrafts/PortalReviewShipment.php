<?php

namespace App\Actions\DesignDrafts;

use Illuminate\Support\Facades\File;

final class PortalReviewShipment
{
    public const ASSETS = ['jayco-logo', 'jayco-overlook', 'canyon'];

    public const OVERRIDES = ['foreground', 'card', 'cardForeground', 'border', 'primary', 'primaryForeground', 'ring'];

    public static function assetPath(string $asset): string
    {
        abort_unless(in_array($asset, self::ASSETS, true), 404);

        return resource_path("fixtures/portal-review/{$asset}.webp");
    }

    public static function shipment(): array
    {
        return File::json(resource_path('fixtures/portal-review/shipment.json'));
    }

    public static function page(array $style = [], bool $classic = false): array
    {
        $colors = $style['colors'] ?? ['brand' => '#185E86', 'accent' => '#B7D9E8', 'paper' => '#F5F8FA'];
        $theme = DraftPalette::theme($colors);
        $derived = $theme['colors']['root'];
        foreach ($style['overrides'] ?? [] as $key => $hex) {
            $theme['colors']['root'][$key] = DraftPalette::colorToken($hex);
        }
        $banner = $style['banner'] ?? 'jayco-overlook';
        $asset = fn (string $key): array => ['name' => $key, 'file_path' => $key.'.webp'];
        $urls = [];
        foreach (['jayco-logo', $banner] as $key) {
            $urls[$key.'.webp'] = route('admin.portal-review.asset', $key, false);
        }

        return [
            'component' => $classic ? 'brandedTracking/Classic' : 'brandedTracking/Journey',
            'props' => [
                'initialCompany' => ['name' => 'Jayco', 'features' => [], 'logo' => $asset('jayco-logo'),
                    'banner' => $asset($banner), 'footer' => null, 'theme' => $theme],
                'initialTrackingData' => self::shipment(), 'initialShipmentDocuments' => [], 'initialShipmentCoordinates' => null,
                'sectionOrder' => DesignCatalog::LAYOUTS['journey.v1']['order'], 'presentationVersion' => 2,
                'headlineStyle' => $style['headline'] ?? 'layout', 'brandImageUrls' => $urls,
                'reviewRadius' => ['square' => '0rem', 'soft' => '0.5rem', 'round' => '1rem'][$style['corners'] ?? 'soft'],
                'reviewDerivedTokens' => $derived,
                'app' => ['name' => 'Prologue Tracking', 'env' => 'production', 'disks' => ['spaces' => []]],
            ],
        ];
    }
}
