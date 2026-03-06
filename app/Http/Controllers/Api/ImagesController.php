<?php

namespace App\Http\Controllers\Api;

use App\Actions\Images\DeleteImage;
use App\Actions\Images\ListImages;
use App\Actions\Images\StoreImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteImageRequest;
use App\Http\Requests\ListImagesRequest;
use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

class ImagesController extends Controller
{
    public function __construct(
        private readonly DeleteImage $deleteImage,
        private readonly ListImages $listImages,
        private readonly StoreImage $storeImage,
    ) {
    }

    public function index(ListImagesRequest $request): JsonResponse
    {
        return response()->json(
            ImageResource::collection(
                $this->listImages->execute($request->integer('image_type_id') ?: null),
            )->resolve(),
            Response::HTTP_OK,
        );
    }

    public function store(StoreImageRequest $request): JsonResponse
    {
        try {
            $image = $this->storeImage->execute(
                $request->validated(),
                $request->user()?->id,
                $request->route()?->getName(),
            );

            return response()->json(
                ImageResource::make($image)->resolve(),
                Response::HTTP_CREATED,
            );
        } catch (Throwable) {
            return response()->json(['error' => 'Failed to store image'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Image $image, DeleteImageRequest $request): JsonResponse
    {
        try {
            $this->deleteImage->execute(
                $image->loadMissing('imageType'),
                $request->user()?->id,
                $request->route()?->getName(),
            );
        } catch (Throwable) {
            return response()->json(['error' => 'Failed to delete image'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
