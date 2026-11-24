<?php

namespace App\Actions\DesignDrafts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class RenderProofImages
{
    public function execute(array $page, string $directory): array
    {
        $company = $page['props']['initialCompany'];
        $images = [];
        $paths = array_column(array_filter([$company['logo'], $company['banner'], $company['footer']]), 'file_path');
        if (! $company['logo'] && isset($page['props']['brandImageUrls'][DesignBrandImages::DEFAULT_LOGO])) {
            $paths[] = DesignBrandImages::DEFAULT_LOGO;
        }
        foreach (array_unique($paths) as $path) {
            $images[$path] = base64_encode($this->readImage($path));
        }

        return $this->render($page, $images, $directory);
    }

    /** @param array<string, string> $images Base64-encoded brand assets keyed by the snapshot's image path. */
    public function render(array $page, array $images, string $directory): array
    {
        $process = new Process([config('tracking_proofs.node'), base_path('tools/proof-renderer/render.mjs'), $directory], base_path());
        $process->setInput(json_encode([
            'page' => $page,
            'images' => (object) $images,
            'chrome' => config('tracking_proofs.chrome'),
            'timezone' => config('tracking_proofs.timezone'),
        ], JSON_THROW_ON_ERROR));
        $process->setTimeout(65);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('The proof browser could not complete both views.');
        }

        $hashes = [];
        foreach (['desktop' => 1440, 'mobile' => 390] as $view => $width) {
            $path = "{$directory}/{$view}.png";
            $size = @getimagesize($path);
            if (! $size || $size[0] !== $width || $size[2] !== IMAGETYPE_PNG) {
                throw new RuntimeException('A proof image is incomplete.');
            }
            $hashes[$view] = hash_file('sha256', $path);
        }

        return $hashes;
    }

    private function readImage(string $path): string
    {
        if (! DesignBrandImages::validPath($path)) {
            throw new RuntimeException('Invalid branding image path.');
        }
        $disk = config('filesystems.image_library_disk');
        if ($disk === 'public' && Storage::disk($disk)->exists($path)) {
            $bytes = Storage::disk($disk)->get($path);
        } else {
            $bucket = config('filesystems.disks.spaces.bucket');
            $region = config('filesystems.disks.spaces.region');
            if (! $bucket || ! $region) {
                throw new RuntimeException('The branding image library is unavailable.');
            }
            $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));
            $response = Http::timeout(8)->withoutRedirecting()->get("https://{$bucket}.{$region}.cdn.digitaloceanspaces.com/{$encoded}");
            $bytes = $response->successful() ? $response->body() : null;
        }
        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('A branding image is unavailable.');
        }

        return $bytes;
    }
}
