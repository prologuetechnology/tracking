<?php

namespace App\Actions\Themes;

use App\Actions\DesignDrafts\DraftPalette;
use Illuminate\Validation\Rule;

final class ResolveThemeStyle
{
    public const OVERRIDES = ['foreground', 'card', 'cardForeground', 'border', 'primary', 'primaryForeground', 'ring'];

    private const RADII = ['square' => '0rem', 'soft' => '0.5rem', 'round' => '1rem'];

    public static function rules(string $key = 'style'): array
    {
        $hex = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/D'];

        return [
            $key => ['required', 'array:colors,headline,corners,overrides'],
            "$key.colors" => ['required', 'array:brand,accent,paper'],
            "$key.colors.brand" => $hex, "$key.colors.accent" => $hex, "$key.colors.paper" => $hex,
            "$key.headline" => ['required', Rule::in(['layout', 'sans', 'serif'])],
            "$key.corners" => ['required', Rule::in(array_keys(self::RADII))],
            "$key.overrides" => ['sometimes', 'array:'.implode(',', self::OVERRIDES)],
            "$key.overrides.*" => $hex,
        ];
    }

    public static function attributes(array $style): array
    {
        $colors = DraftPalette::theme($style['colors'])['colors'];
        foreach ($style['overrides'] ?? [] as $key => $hex) {
            $colors['root'][$key] = DraftPalette::colorToken($hex);
        }

        return ['colors' => $colors, 'radius' => self::RADII[$style['corners']]];
    }
}
