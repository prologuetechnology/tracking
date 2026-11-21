<?php

namespace App\Actions\DesignDrafts;

use Illuminate\Support\Arr;

final class DesignCatalog
{
    public const MODULES = [
        'receiving' => 'Receiving address',
        'references' => 'Shipment references',
        'map' => 'Tracking map',
        'documents' => 'Shipment documents',
        'instructions' => 'Special instructions',
    ];

    public const LAYOUTS = [
        'classic.v1' => [
            'name' => 'Classic',
            'component' => 'brandedTracking/Classic',
            'description' => 'The original branded tracking page.',
            'placement' => 'Fixed shipment details and history',
            'order' => [],
        ],
        'answer.v1' => [
            'name' => 'Get the answer',
            'component' => 'brandedTracking/Answer',
            'description' => 'Confirm a delivery at a glance.',
            'placement' => 'Below the shipment status',
            'order' => ['receiving', 'references', 'instructions', 'documents', 'map'],
        ],
        'journey.v1' => [
            'name' => 'Follow the journey',
            'component' => 'brandedTracking/Journey',
            'description' => 'Follow the route and its carrier updates.',
            'placement' => 'In the itinerary beside the travel log',
            'order' => ['map', 'receiving', 'instructions', 'references', 'documents'],
        ],
        'record.v1' => [
            'name' => 'Inspect the record',
            'component' => 'brandedTracking/Record',
            'description' => 'Match paperwork to the carrier’s scans.',
            'placement' => 'At the top of the shipment file',
            'order' => ['references', 'documents', 'receiving', 'instructions', 'map'],
        ],
    ];

    public const PALETTES = [
        'company' => [
            'name' => 'Company theme',
            'color' => '#626978',
        ],
        'custom' => ['name' => 'Custom colors', 'color' => null],
        'lake' => [
            'name' => 'Lake blue',
            'color' => '#185e86',
            'primary' => '202 70% 31%',
            'background' => '195 33% 98%',
            'foreground' => '200 54% 18%',
            'muted' => '202 27% 93%',
        ],
        'pine' => [
            'name' => 'Forest green',
            'color' => '#24664f',
            'primary' => '160 48% 27%',
            'background' => '60 30% 96%',
            'foreground' => '155 24% 19%',
            'muted' => '135 16% 91%',
        ],
        'clay' => [
            'name' => 'Canyon clay',
            'color' => '#87452b',
            'primary' => '17 52% 35%',
            'background' => '33 47% 96%',
            'foreground' => '20 27% 22%',
            'muted' => '27 24% 90%',
        ],
        'iris' => [
            'name' => 'Indigo',
            'color' => '#483a88',
            'primary' => '251 40% 38%',
            'background' => '252 38% 97%',
            'foreground' => '249 26% 24%',
            'muted' => '251 22% 92%',
        ],
        'slate' => [
            'name' => 'Slate',
            'color' => '#27272a',
            'primary' => '240 6% 16%',
            'background' => '220 23% 97%',
            'foreground' => '218 13% 17%',
            'muted' => '216 12% 91%',
        ],
    ];

    public static function orders(): array
    {
        return array_filter(array_map(fn ($layout) => $layout['order'], self::LAYOUTS));
    }

    public static function editor(): array
    {
        return [
            'layouts' => collect(self::LAYOUTS)
                ->map(fn ($layout, $id) => ['id' => $id, ...Arr::except($layout, 'component')])
                ->values()->all(),
            'palettes' => collect(self::PALETTES)
                ->map(fn ($palette, $id) => ['id' => $id, ...Arr::only($palette, ['name', 'color'])])
                ->values()->all(),
            'modules' => self::MODULES,
        ];
    }

    public static function preset(string $id): ?array
    {
        if ($id === 'company') {
            return null;
        }

        $palette = self::PALETTES[$id];

        return ['name' => $palette['name'], 'colors' => ['root' => [
            'primary' => $palette['primary'], 'primaryForeground' => '0 0% 100%',
            'background' => $palette['background'], 'foreground' => $palette['foreground'],
            'card' => '0 0% 100%', 'cardForeground' => $palette['foreground'],
            'popover' => '0 0% 100%', 'popoverForeground' => $palette['foreground'],
            'muted' => $palette['muted'], 'mutedForeground' => '215 12% 36%',
            'secondary' => $palette['muted'], 'secondaryForeground' => $palette['foreground'],
            'accent' => $palette['muted'], 'accentForeground' => $palette['foreground'],
            'border' => '215 12% 81%', 'input' => '215 12% 76%', 'ring' => $palette['primary'],
        ]]];
    }
}
