<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\Images\ListImages;
use App\Actions\Images\ListImageTypes;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImageResource;
use App\Http\Resources\ImageTypeResource;
use Inertia\Inertia;
use Inertia\Response;

class ImagePageController extends Controller
{
    public function __construct(
        private readonly ListImages $listImages,
        private readonly ListImageTypes $listImageTypes,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/image/Index', [
            'initialImages' => ImageResource::collection(
                $this->listImages->execute(),
            )->resolve(),
            'initialImageTypes' => ImageTypeResource::collection(
                $this->listImageTypes->execute(),
            )->resolve(),
        ]);
    }
}
