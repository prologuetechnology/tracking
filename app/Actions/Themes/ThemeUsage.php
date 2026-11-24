<?php

namespace App\Actions\Themes;

use App\Actions\DesignDrafts\ClassicDesign;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\Theme;
use Illuminate\Support\Collection;

final class ThemeUsage
{
    public static function forTheme(Theme $theme, bool $includeDrafts): Collection
    {
        $usage = collect();
        $companies = Company::query()
            ->where('theme_id', $theme->id)
            ->orWhereHas('latestPublication.proof.revision', fn ($q) => $q->where('design->theme->id', $theme->id))
            ->orWhereHas('latestPublication.baselineRevision', fn ($q) => $q->where('design->theme->id', $theme->id))
            ->with(['theme:id', 'themeVersion', 'latestPublication.proof.revision', 'latestPublication.baselineRevision'])
            ->orderBy('name')->get();
        foreach ($companies as $company) {
            $publication = $company->latestPublication;
            $design = $publication?->proof?->revision?->design ?? $publication?->baselineRevision?->design;
            $snapshot = $design ? self::effectiveTheme($design) : $company->baselineTheme()?->snapshot();
            if (($snapshot['id'] ?? null) == $theme->id) {
                $usage->push(['kind' => 'live', 'version' => $snapshot['version'] ?? null, 'company_uuid' => $company->uuid,
                    'company_name' => $company->name, 'draft_uuid' => null, 'draft_name' => null]);
            }
        }
        if ($includeDrafts) {
            $drafts = DesignDraft::query()->whereHas('latestRevision', fn ($q) => $q->where('design->theme->id', $theme->id))
                ->with('company:id,uuid,name', 'latestRevision')->orderBy('name')->get();
            foreach ($drafts as $draft) {
                $snapshot = self::effectiveTheme($draft->latestRevision->design);
                if ($snapshot) {
                    $usage->push(['kind' => 'draft', 'version' => $snapshot['version'] ?? null, 'company_uuid' => $draft->company->uuid,
                        'company_name' => $draft->company->name, 'draft_uuid' => $draft->uuid, 'draft_name' => $draft->name]);
                }
            }
        }

        return $usage;
    }

    public static function effectiveTheme(array $design): ?array
    {
        return $design['layout'] === ClassicDesign::LAYOUT || ($design['palette'] ?? 'company') === 'company'
            ? ($design['theme'] ?? null) : null;
    }
}
