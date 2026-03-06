<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()?->can('company:show')) {
            return redirect()->route('admin.companies.index');
        }

        return redirect()->route('admin.tracking.index');
    }
}
