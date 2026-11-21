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

        $urls = [];
        foreach (array_unique($paths) as $path) {
            $url = self::urlForPath($path);
            if ($url !== null) {
                $urls[$path] = $url;
            }
        }

        return $urls;
    }

    public static function urlForPath(string $path): ?string
    {
        if (! self::validPath($path)) {
            return null;
        }
        if (config('filesystems.image_library_disk') === 'public' && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }
        $bucket = config('filesystems.disks.spaces.bucket');
        $region = config('filesystems.disks.spaces.region');
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));

        return $bucket && $region ? "https://{$bucket}.{$region}.cdn.digitaloceanspaces.com/{$encoded}" : null;
    }

    public static function validPath(string $path): bool
    {
        return $path !== '' && ! str_starts_with($path, '/') && ! str_contains($path, '\\')
            && ! in_array('..', explode('/', $path), true);
    }
}
