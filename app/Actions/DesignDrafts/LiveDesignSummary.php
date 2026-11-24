<?php

namespace App\Actions\DesignDrafts;

use App\Actions\Themes\ThemeUsage;
use App\Models\Company;
use App\Models\ThemeVersion;
use App\Models\TrackingPublication;

final class LiveDesignSummary
{
    public static function forPublication(?TrackingPublication $publication, Company $company): array
    {
        $revision = $publication?->effectiveRevision();
        $theme = $revision ? ThemeUsage::effectiveTheme($revision->design) : $company->baselineTheme()?->snapshot();

        return [
            'label' => $publication?->design_proof_id ? $revision->draft->name.' · Revision '.$revision->version : 'Original Classic',
            'layout' => $revision?->design['layout'] ?? ClassicDesign::LAYOUT, 'publicationId' => $publication?->id,
            'themeId' => $theme['id'] ?? null, 'themeName' => $theme['name'] ?? null, 'themeVersion' => $theme['version'] ?? null, 'themeVersionId' => $theme['theme_version_id'] ?? null,
            'latestThemeVersion' => isset($theme['id']) ? ThemeVersion::where('theme_id', $theme['id'])->max('version') : null,
        ];
    }
}
