<?php

namespace App\Actions\Themes;

use App\Models\Company;
use App\Models\DesignDraftRevision;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ArchiveTheme
{
    public function execute(Theme $theme): void
    {
        DB::transaction(function () use ($theme) {
            $theme = Theme::query()->lockForUpdate()->findOrFail($theme->id);
            if (Company::where('theme_id', $theme->id)->exists() || DesignDraftRevision::where('design->theme->id', $theme->id)->exists()) {
                throw ValidationException::withMessages(['theme' => 'This style is assigned to a company or saved revision. Its versions must remain available.']);
            }
            $theme->delete();
        }, attempts: 3);
    }
}
