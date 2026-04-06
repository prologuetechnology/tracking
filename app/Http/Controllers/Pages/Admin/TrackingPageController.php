<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TrackingPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/tracking/Index');
    }
}
