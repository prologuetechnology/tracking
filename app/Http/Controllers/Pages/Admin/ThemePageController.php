<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;

class ThemePageController extends Controller
{
    public function index(): RedirectResponse
    {
        return to_route('admin.studio.styles.index');
    }

    public function create(): RedirectResponse
    {
        return to_route('admin.studio.styles.create');
    }

    public function show(Theme $theme): RedirectResponse
    {
        return to_route('admin.studio.styles.show', $theme->uuid);
    }
}
