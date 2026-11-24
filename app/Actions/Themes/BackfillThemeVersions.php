<?php

namespace App\Actions\Themes;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BackfillThemeVersions
{
    public function execute(): void
    {
        DB::table('themes')->select('id')->chunkById(100, function ($themes) {
            foreach ($themes as $row) {
                DB::transaction(function () use ($row) {
                    $theme = DB::table('themes')->where('id', $row->id)->lockForUpdate()->firstOrFail();
                    $versions = DB::table('theme_versions')->where('theme_id', $theme->id);
                    $first = (clone $versions)->where('version', 1)->first();
                    if (! $first) {
                        $versionId = $versions->insertGetId([
                            'theme_id' => $theme->id, 'version' => 1, 'name' => $theme->name,
                            'colors' => $theme->colors, 'radius' => $theme->radius, 'style' => $theme->style,
                            'created_at' => $theme->created_at,
                        ]);
                    } else {
                        if (! (clone $versions)->where('version', '>', 1)->exists()) {
                            $expectedColors = json_decode($theme->colors, true, flags: JSON_THROW_ON_ERROR);
                            $savedColors = json_decode($first->colors, true, flags: JSON_THROW_ON_ERROR);
                            if ($theme->radius !== $first->radius || $expectedColors !== $savedColors) {
                                throw new RuntimeException("Stored colors or radius differ from version 1 of theme {$theme->id}.");
                            }
                        }
                        $versionId = $first->id;
                    }
                    DB::table('companies')->where('theme_id', $theme->id)->whereNull('theme_version_id')
                        ->update(['theme_version_id' => $versionId]);
                }, attempts: 3);
            }
        });

        $mismatched = DB::table('companies as c')->join('themes as t', 'c.theme_id', '=', 't.id')
            ->leftJoin('theme_versions as v', 'c.theme_version_id', '=', 'v.id')
            ->where(fn ($query) => $query->whereNull('v.id')->orWhereColumn('v.theme_id', '!=', 't.id'))->exists();
        if ($mismatched) {
            throw new RuntimeException('One or more existing company theme assignments could not be reconciled.');
        }
    }
}
