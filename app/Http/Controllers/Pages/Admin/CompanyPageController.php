<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\Companies\ListCompanies;
use App\Actions\Companies\ShowCompany;
use App\Actions\DesignDrafts\CompanyDesignWorkspace;
use App\Actions\DesignDrafts\DraftShipmentLookup;
use App\Actions\Images\ListImageTypes;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyFeatureResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ImageTypeResource;
use App\Models\Company;
use App\Models\CompanyFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyPageController extends Controller
{
    public function __construct(
        private readonly ListCompanies $listCompanies,
        private readonly ListImageTypes $listImageTypes,
        private readonly ShowCompany $showCompany,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->query('workspace') === 'studio') {
            return to_route('admin.companies.index', $request->except('workspace'));
        }

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

    public function show(Company $company, Request $request, CompanyDesignWorkspace $workspace): Response
    {
        $lookup = DraftShipmentLookup::fromQuery($request);

        return Inertia::render('admin/companies/Edit', [
            'shipmentLookup' => $lookup ?: null,
            'designWorkspace' => $workspace->execute($company, $request->user()->can('company:update')),
            'companyInitialValues' => CompanyResource::make(
                $this->showCompany->execute($company),
            )->resolve(),
            'companyFeaturesInitialValues' => CompanyFeatureResource::collection(
                CompanyFeature::query()->orderBy('id', 'desc')->get(),
            )->resolve(),
            'initialImageTypes' => ImageTypeResource::collection(
                $this->listImageTypes->execute(),
            )->resolve(),
        ]);
    }
}
