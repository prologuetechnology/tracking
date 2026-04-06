<?php

namespace Tests\Unit;

use App\Actions\GenerateThemeColors;
use PHPUnit\Framework\TestCase;

class GenerateThemeColorsTest extends TestCase
{
    public function test_it_derives_root_tokens_from_the_primary_hue_when_requested(): void
    {
        $colors = GenerateThemeColors::execute([
            'primary_hue' => [210],
            'primary_saturation' => [70],
            'primary_lightness' => [40],
            'accent_hue' => [18],
            'accent_saturation' => [80],
            'accent_lightness' => [55],
            'derive_from' => 'primary',
        ]);

        $this->assertSame('210 70% 99%', $colors['root']['background']);
        $this->assertSame('210 70% 40%', $colors['root']['primary']);
        $this->assertSame('18 80% 55%', $colors['root']['accent']);
    }

    public function test_it_derives_root_tokens_from_the_accent_hue_when_requested(): void
    {
        $colors = GenerateThemeColors::execute([
            'primary_hue' => [210],
            'primary_saturation' => [70],
            'primary_lightness' => [40],
            'accent_hue' => [18],
            'accent_saturation' => [80],
            'accent_lightness' => [55],
            'derive_from' => 'accent',
        ]);

        $this->assertSame('18 70% 99%', $colors['root']['background']);
        $this->assertSame('18 85% 80%', $colors['root']['ring']);
    }
}
