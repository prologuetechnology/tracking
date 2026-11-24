<?php

namespace App\Actions\Themes;

use App\Actions\GenerateThemeColors;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

class CreateTheme
{
    public function execute(array $attributes): Theme
    {
        return DB::transaction(fn () => Theme::create(self::attributes($attributes)), attempts: 3);
    }

    public static function attributes(array $input): array
    {
        $style = $input['style'] ?? null;
        $appearance = $style === null
            ? ['colors' => GenerateThemeColors::execute($input), 'radius' => $input['radius'] ?? '0.5rem']
            : ResolveThemeStyle::attributes($style);

        return [
            ...$appearance, 'style' => $style, 'name' => $input['name'],
            'derive_from' => $input['derive_from'] ?? 'primary', 'is_system' => $input['is_system'] ?? false,
        ];
    }
}
