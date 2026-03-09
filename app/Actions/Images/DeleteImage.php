<?php

namespace App\Actions\Images;

use App\Models\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteImage
{
    public function execute(Image $image, ?int $userId = null, ?string $route = null): void
    {
        $disk = (string) config('filesystems.image_library_disk', 'spaces');

        try {
            if ($image->file_path !== '') {
                Storage::disk($disk)->delete($image->file_path);
            }

            $image->delete();
        } catch (Throwable $exception) {
            Log::error('Image delete failed.', [
                'action' => 'image.destroy',
                'user_id' => $userId,
                'image_id' => $image->id,
                'disk' => $disk,
                'route' => $route,
                'status' => 500,
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
