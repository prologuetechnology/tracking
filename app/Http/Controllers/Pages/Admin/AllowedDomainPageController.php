<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\AllowedDomains\ListAllowedDomains;
use App\Http\Controllers\Controller;
use App\Http\Resources\AllowedDomainResource;
use Inertia\Inertia;
use Inertia\Response;

class AllowedDomainPageController extends Controller
{
    public function __construct(
        private readonly ListAllowedDomains $listAllowedDomains,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/allowedDomains/Index', [
            'initialAllowedDomains' => AllowedDomainResource::collection(
                $this->listAllowedDomains->execute(),
            )->resolve(),
        ]);
    }
}
