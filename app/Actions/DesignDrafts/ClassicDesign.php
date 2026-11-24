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

    public static function visibleSections(Company $company, array $design): array
    {
        $hidden = $design['hidden_modules'][$design['layout']] ?? [];
        $gates = ['map' => 'enable_map', 'documents' => 'enable_documents', 'instructions' => 'special_instructions', 'references' => 'customer_pos'];
        $enabled = array_intersect($design['sections'], self::availableSections($company));

        return array_values(array_diff($enabled, array_intersect_key($gates, array_flip($hidden))));
    }

    /** Return the effective editable values, including defaults absent from older snapshots. */
    public static function editorSettings(Company $company, ?array $design): array
    {
        $orders = DesignCatalog::orders();
        $available = self::availableSections($company);
        $hidden = array_replace(array_fill_keys(array_keys($orders), []), $design['hidden_modules'] ?? []);

        $baseline = $design === null ? $company->baselineTheme() : null;
        $settings = [
            'layout' => $design['layout'] ?? self::LAYOUT,
            'emphasis' => $design['emphasis'] ?? 'delivery',
            'palette' => $design['palette'] ?? 'company',
            'theme_id' => $design === null ? $baseline?->theme_id : ($design['theme']['id'] ?? null),
            'theme_version_id' => $design === null ? $baseline?->id : ($design['theme']['theme_version_id'] ?? null),
            'headline_style' => $design['headline_style'] ?? 'layout',
            'custom_colors' => array_map(strtoupper(...), $design['custom_colors'] ?? DraftPalette::DEFAULT_COLORS),
            'section_orders' => array_replace($orders, $design['section_orders'] ?? []),
            'hidden_modules' => $hidden,
            'sections' => array_values(array_intersect($available, $design['sections'] ?? $available)),
        ];
        foreach (self::ASSETS as $type) {
            $field = $type.'_image_id';
            $settings[$field] = $design === null ? $company->$field : ($design[$type]['id'] ?? null);
        }

        return $settings;
    }

    private function resolveTheme(Company $company, ?int $themeId, ?int $versionId, ?array $previous): ?array
    {
        if ($themeId === null && $versionId === null) {
            return null;
        }
        if ($previous && $themeId === $previous['id'] && ($versionId === null || $versionId === ($previous['theme_version_id'] ?? null))) {
            return $previous;
        }
        if ($themeId === $company->theme_id && ($versionId === null || $versionId === $company->theme_version_id)) {
            return $company->baselineTheme()?->snapshot();
        }
        $theme = Theme::query()->lockForUpdate()->find($themeId);
        $version = $versionId === null ? $theme?->latestVersion : $theme?->versions()->find($versionId);
        if (! $version) {
            throw ValidationException::withMessages(['theme_id' => 'Choose an available version of this theme.']);
        }

        return $version->snapshot();
    }

    public function snapshot(Company $company, array $input, ?DesignDraftRevision $previous): array
    {
        if ($previous === null && isset($input['source_publication_id'])) {
            $previous = $company->publications()->findOrFail($input['source_publication_id'])->effectiveRevision();
        }

        $previousTheme = $previous?->design['theme'] ?? null;
        $themeId = $input['theme_id'];
        $versionId = $input['theme_version_id'] ?? null;
        $theme = $this->resolveTheme($company, $themeId, $versionId, $previousTheme);

        $palette = $input['palette'] ?? $previous?->design['palette'] ?? 'company';
        $customColors = array_map(strtoupper(...), $input['custom_colors'] ?? $previous?->design['custom_colors'] ?? DraftPalette::DEFAULT_COLORS);
        $paletteTheme = $palette === 'custom' ? DraftPalette::theme($customColors) : DesignCatalog::preset($palette);
        if ($palette === ($previous?->design['palette'] ?? null) && ($palette !== 'custom' || $customColors == ($previous->design['custom_colors'] ?? []))) {
            $paletteTheme = $previous->design['palette_theme'] ?? $paletteTheme;
        }

        $design = [
            'presentation_version' => 2,
            'custom_colors' => $customColors,
            'hidden_modules' => array_replace($previous?->design['hidden_modules'] ?? [], $input['hidden_modules'] ?? []),
            'palette' => $palette,
            'palette_theme' => $paletteTheme,
            'headline_style' => $input['headline_style'] ?? $previous?->design['headline_style'] ?? 'layout',
            'section_orders' => array_replace(DesignCatalog::orders(), $previous?->design['section_orders'] ?? [], $input['section_orders'] ?? []),
            'layout' => $input['layout'] ?? $previous?->design['layout'] ?? self::LAYOUT,
            'emphasis' => $input['emphasis'] ?? $previous?->design['emphasis'] ?? 'delivery',
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

        if ($previous && ($previous->design['presentation_version'] ?? 1) < 2 && ! ($input['upgrade_presentation'] ?? false)
            && self::editorSettings($company, $previous->design) == self::editorSettings($company, $design)) {
            // Keep the original shape: historical Answer layouts also dispatch on the absence of section_orders.
            return [...$previous->design, 'sections' => $design['sections']];
        }

        return $design;
    }
}
