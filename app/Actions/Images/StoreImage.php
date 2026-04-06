<?php

namespace App\Actions\Images;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class StoreImage
{
    public function execute(array $attributes, ?int $userId = null, ?string $route = null): Image
    {
        /** @var UploadedFile $uploadedImage */
        $uploadedImage = $attributes['image'];
        $disk = (string) config('filesystems.image_library_disk', 'spaces');

        try {
            $filePath = $uploadedImage->store('images', $disk);

            if (! $filePath) {
                throw new RuntimeException('Image storage path is empty.');
            }

            return Image::query()->create([
                'name' => $attributes['name'],
                'image_type_id' => $attributes['image_type_id'],
                'file_path' => $filePath,
            ])->load('imageType');
        } catch (Throwable $exception) {
            Log::error('Image store failed.', [
                'action' => 'image.store',
                'user_id' => $userId,
                'image_type_id' => $attributes['image_type_id'] ?? null,
                'disk' => $disk,
                'route' => $route,
                'status' => 500,
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
