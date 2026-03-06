<?php

namespace App\Http\Controllers\Api;

use App\Actions\Images\ListImageTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListImageTypesRequest;
use App\Http\Resources\ImageTypeResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ImageTypesController extends Controller
{
    public function __construct(
        private readonly ListImageTypes $listImageTypes,
    ) {
    }

    public function index(ListImageTypesRequest $request): JsonResponse
    {
        return response()->json(
            ImageTypeResource::collection($this->listImageTypes->execute())->resolve(),
            Response::HTTP_OK,
        );
    }
}
