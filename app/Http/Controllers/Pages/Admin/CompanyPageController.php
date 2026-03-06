<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\Companies\ListCompanies;
use App\Actions\Companies\ShowCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyFeatureResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\CompanyFeature;
use Inertia\Inertia;
use Inertia\Response;

class CompanyPageController extends Controller
{
    public function __construct(
        private readonly ListCompanies $listCompanies,
        private readonly ShowCompany $showCompany,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/companies/Index', [
            'initialCompanies' => CompanyResource::collection(
                $this->listCompanies->execute(),
            )->resolve(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/companies/Create');
    }

    public function show(Company $company): Response
    {
        return Inertia::render('admin/companies/Edit', [
            'companyInitialValues' => CompanyResource::make(
                $this->showCompany->execute($company),
            )->resolve(),
            'companyFeaturesInitialValues' => CompanyFeatureResource::collection(
                CompanyFeature::query()->orderBy('id', 'desc')->get(),
            )->resolve(),
        ]);
    }
}
