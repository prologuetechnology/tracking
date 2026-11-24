<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;

final class CompanyDesignWorkspace
{
    public function execute(Company $company, bool $canDesign): array
    {
        $publication = $company->publications()->latest('id')->first();
        $live = LiveDesignSummary::forPublication($publication, $company);
        $overview = [
            'layout' => DesignCatalog::LAYOUTS[$live['layout']]['name'] ?? 'Classic',
            'liveTheme' => $live['themeId'] === null ? null : [
                'name' => $live['themeName'], 'version' => $live['themeVersion'], 'latestVersion' => $live['latestThemeVersion'],
            ],
        ];
        if (! $canDesign) {
            return $overview;
        }

        $revision = $publication?->publishedRevision;

        return [
            ...$overview,
            'liveRevision' => $revision ? ['draftUuid' => $revision->draft->uuid, 'version' => $revision->version] : null,
            'live' => $live['label'],
        ];
    }
}
