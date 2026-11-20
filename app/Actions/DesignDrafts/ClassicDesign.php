<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;
use App\Models\DesignDraftRevision;
use App\Models\Image;
use App\Models\Theme;
use Illuminate\Validation\ValidationException;

class ClassicDesign
{
    public const LAYOUT = 'classic.v1';

    public const ASSETS = ['logo', 'banner', 'footer'];

    public const SECTIONS = [
        'enable_map' => 'Tracking map',
        'enable_documents' => 'Shipment documents',
        'customer_pos' => 'Customer POs',
        'special_instructions' => 'Special instructions',
    ];

    public static function availableSections(Company $company): array
    {
        $company->loadMissing('features');

        return array_values(array_filter(array_keys(self::SECTIONS), $company->hasFeature(...)));
    }

    public function snapshot(Company $company, array $input, ?DesignDraftRevision $previous): array
    {
        $previousTheme = $previous?->design['theme'] ?? null;
        $themeId = $input['theme_id'];
        $theme = null;
        if ($themeId !== null) {
            $theme = (int) $themeId === ($previousTheme['id'] ?? null)
                ? $previousTheme
                : Theme::query()->find($themeId)?->only(['id', 'name', 'colors', 'radius']);
            if ($theme === null) {
                throw ValidationException::withMessages(['theme_id' => 'Choose an available theme.']);
            }
        }

        $design = [
            'layout' => self::LAYOUT,
            'theme' => $theme,
            'sections' => array_values(array_intersect(self::availableSections($company), $input['sections'])),
        ];
        foreach (self::ASSETS as $type) {
            $key = $type.'_image_id';
            $design[$type] = null;
            if ($input[$key] !== null) {
                $image = Image::query()->whereHas('imageType', fn ($q) => $q->where('name', $type))
                    ->lockForUpdate()->find($input[$key]);
                if (! $image) {
                    throw ValidationException::withMessages([$key => "Choose an available {$type} image."]);
                }
                $design[$type] = $image->only(['id', 'name', 'file_path']);
            }
        }

        return $design;
    }
}
