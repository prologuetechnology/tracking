<?php

namespace App\Actions\DesignDrafts;

use Illuminate\Support\Facades\Storage;

class DesignBrandImages
{
    public const DEFAULT_LOGO = 'images/FW_Logo_Full_Color.png';

    public static function urls(array $design): array
    {
        $paths = array_column(array_filter([$design['logo'], $design['banner'], $design['footer']]), 'file_path');
        if (! $design['logo'] && $design['layout'] === ClassicDesign::LAYOUT) {
            $paths[] = self::DEFAULT_LOGO;
        }

        $bucket = config('filesystems.disks.spaces.bucket');
        $region = config('filesystems.disks.spaces.region');
        $urls = [];
        foreach (array_unique($paths) as $path) {
            if (! self::validPath($path)) {
                continue;
            }
            if (config('filesystems.image_library_disk') === 'public' && Storage::disk('public')->exists($path)) {
                $urls[$path] = Storage::disk('public')->url($path);
            } elseif ($bucket && $region) {
                $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));
                $urls[$path] = "https://{$bucket}.{$region}.cdn.digitaloceanspaces.com/{$encoded}";
            }
        }

        return $urls;
    }

    public static function validPath(string $path): bool
    {
        return $path !== '' && ! str_starts_with($path, '/') && ! str_contains($path, '\\')
            && ! in_array('..', explode('/', $path), true);
    }
}
