<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\Themes\ListThemes;
use App\Http\Controllers\Controller;
use App\Http\Resources\ThemeResource;
use App\Models\Theme;
use Inertia\Inertia;
use Inertia\Response;

class ThemePageController extends Controller
{
    public function __construct(
        private readonly ListThemes $listThemes,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/themes/Index', [
            'initialThemes' => ThemeResource::collection(
                $this->listThemes->execute(),
            )->resolve(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/themes/Create');
    }

    public function show(Theme $theme): Response
    {
        return Inertia::render('admin/themes/Edit', [
            'initialTheme' => ThemeResource::make($theme)->resolve(),
        ]);
    }
}
