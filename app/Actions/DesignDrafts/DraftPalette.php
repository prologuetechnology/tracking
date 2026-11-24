<?php

namespace App\Actions\DesignDrafts;

final class DraftPalette
{
    public const DEFAULT_COLORS = ['brand' => '#185E86', 'accent' => '#B7D9E8', 'paper' => '#F5F8FA'];

    public static function theme(array $colors): array
    {
        $brand = self::rgb($colors['brand']);
        $accent = self::rgb($colors['accent']);
        $paper = self::rgb($colors['paper']);
        $ink = self::textOn($paper);
        $surface = self::mix($paper, $ink === [0, 0, 0] ? [255, 255, 255] : [0, 0, 0], 0.25);
        // Preserve the exact brand on filled surfaces. Links need enough contrast on the paper.
        $primary = self::legible($brand, $paper);
        $root = [
            'brand' => $brand, 'brandForeground' => self::textOn($brand),
            'primary' => $primary, 'primaryForeground' => self::textOn($primary),
            'accent' => $accent, 'accentForeground' => self::textOn($accent),
            'background' => $paper, 'foreground' => $ink,
            'card' => $surface, 'cardForeground' => $ink,
            'popover' => $surface, 'popoverForeground' => $ink,
            'secondary' => $surface, 'secondaryForeground' => $ink,
            'muted' => $surface, 'mutedForeground' => self::legible(self::mix($ink, $paper, 0.35), $paper),
            'border' => self::mix($paper, $ink, 0.25),
            'input' => self::mix($paper, $ink, 0.5), 'ring' => $primary,
        ];

        return ['name' => 'Custom colors', 'colors' => ['root' => array_map(self::hsl(...), $root)]];
    }

    public static function colorToken(string $hex): string
    {
        return self::hsl(self::rgb($hex));
    }

    private static function rgb(string $hex): array
    {
        return array_map(hexdec(...), str_split(substr($hex, 1), 2));
    }

    private static function mix(array $from, array $to, float $amount): array
    {
        return array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $amount), $from, $to);
    }

    private static function luminance(array $rgb): float
    {
        [$r, $g, $b] = array_map(function ($byte) {
            $channel = $byte / 255;

            return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function contrast(array $a, array $b): float
    {
        $first = self::luminance($a) + 0.05;
        $second = self::luminance($b) + 0.05;

        return max($first, $second) / min($first, $second);
    }

    private static function textOn(array $background): array
    {
        $white = [255, 255, 255];
        $black = [0, 0, 0];

        return self::contrast($black, $background) >= self::contrast($white, $background) ? $black : $white;
    }

    private static function legible(array $color, array $paper): array
    {
        $ink = self::textOn($paper);
        for ($step = 0; $step < 20; $step++) {
            $candidate = self::mix($color, $ink, $step / 20);
            if (self::contrast($candidate, $paper) >= 4.52 && self::textOn($candidate) !== $ink) {
                return $candidate;
            }
        }

        return $ink;
    }

    private static function hsl(array $rgb): string
    {
        [$r, $g, $b] = array_map(fn ($byte) => $byte / 255, $rgb);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $range = $max - $min;
        $lightness = ($max + $min) / 2;
        $hue = 0;
        $saturation = 0;
        if ($range > 0) {
            $saturation = $range / (1 - abs(2 * $lightness - 1));
            $sector = match ($max) {
                $r => ($g - $b) / $range,
                $g => ($b - $r) / $range + 2,
                default => ($r - $g) / $range + 4,
            };
            $hue = fmod($sector * 60 + 360, 360);
        }

        return round($hue, 2).' '.round($saturation * 100, 2).'% '.round($lightness * 100, 2).'%';
    }
}
