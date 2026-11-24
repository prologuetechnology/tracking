<?php

namespace App\Actions\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTheme
{
    public function execute(Theme $theme, array $attributes): Theme
    {
        return DB::transaction(function () use ($theme, $attributes) {
            $theme = Theme::query()->lockForUpdate()->findOrFail($theme->id);
            $latest = $theme->latestVersion()->firstOrFail();
            if ($latest->version !== (int) $attributes['base_version']) {
                throw ValidationException::withMessages(['base_version' => 'Another style version was saved. Reopen this theme to continue.']);
            }

            if (isset($attributes['source_version_id'])) {
                $source = $theme->versions()->find($attributes['source_version_id']);
                if (! $source) {
                    throw ValidationException::withMessages(['source_version_id' => 'Choose a version of this theme.']);
                }
                $theme->fill([...$source->only(['colors', 'style', 'radius']), 'name' => $attributes['name']]);
            } else {
                $theme->fill(CreateTheme::attributes([...$theme->only(['derive_from', 'is_system']), ...$attributes]));
            }
            $theme->save();
            $theme->appendVersion($latest->version + 1);

            return $theme->load('latestVersion', 'versions');
        }, attempts: 3);
    }
}
