<?php

namespace App\Actions\Images;

use App\Models\DesignDraftRevision;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeleteImage
{
    public function execute(Image $image, ?int $userId = null, ?string $route = null): void
    {
        $disk = (string) config('filesystems.image_library_disk', 'spaces');

        try {
            DB::transaction(function () use ($image, $disk) {
                $locked = Image::query()->lockForUpdate()->findOrFail($image->id);
                if (DesignDraftRevision::query()->where('logo_image_id', $locked->id)
                    ->orWhere('banner_image_id', $locked->id)->orWhere('footer_image_id', $locked->id)->exists()) {
                    throw ValidationException::withMessages(['image' => 'A saved design revision uses this image.']);
                }

                $locked->delete();
                if ($locked->file_path !== '') {
                    Storage::disk($disk)->delete($locked->file_path);
                }
            });
        } catch (ValidationException $exception) {
            throw $exception;
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
