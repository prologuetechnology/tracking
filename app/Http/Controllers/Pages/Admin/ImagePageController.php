<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Inertia\Inertia;
use Inertia\Response;

class ImagePageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/image/Index', [
            'initialImages' => Image::query()
                ->with('imageType')
                ->get(),
        ]);
    }
}
