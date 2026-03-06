<?php

namespace App\Actions\Themes;

use App\Actions\GenerateThemeColors;
use App\Models\Theme;

class UpdateTheme
{
    public function execute(Theme $theme, array $attributes): Theme
    {
        $theme->update([
            'name' => $attributes['name'],
            'colors' => GenerateThemeColors::execute($attributes),
            'radius' => $attributes['radius'] ?? '0.5rem',
            'is_system' => $attributes['is_system'] ?? false,
            'derive_from' => $attributes['derive_from'],
        ]);

        return $theme->refresh();
    }
}
