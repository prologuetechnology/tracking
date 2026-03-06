<?php

namespace App\Actions\Themes;

use App\Actions\GenerateThemeColors;
use App\Models\Theme;

class CreateTheme
{
    public function execute(array $attributes): Theme
    {
        return Theme::query()->create([
            'name' => $attributes['name'],
            'colors' => GenerateThemeColors::execute($attributes),
            'radius' => $attributes['radius'] ?? '0.5rem',
            'is_system' => $attributes['is_system'] ?? false,
            'derive_from' => $attributes['derive_from'],
        ]);
    }
}
