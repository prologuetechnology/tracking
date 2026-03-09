<?php

namespace App\Http\Controllers\Api;

use App\Actions\Companies\CreateCompany;
use App\Actions\Companies\ClearCompanyImageAsset;
use App\Actions\Companies\ListCompanies;
use App\Actions\Companies\SetCompanyImageAsset;
use App\Actions\Companies\SetCompanyTheme;
use App\Actions\Companies\ShowCompany;
use App\Actions\Companies\ToggleCompanyField;
use App\Actions\Companies\UpdateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteCompanyRequest;
use App\Http\Requests\ClearCompanyImageAssetRequest;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\ToggleCompanyStatusRequest;
use App\Http\Requests\UpdateCompanyImageAssetRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Requests\UpdateCompanyThemeRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CreateCompany $createCompany,
        private readonly ClearCompanyImageAsset $clearCompanyImageAsset,
        private readonly ListCompanies $listCompanies,
        private readonly SetCompanyImageAsset $setCompanyImageAsset,
        private readonly SetCompanyTheme $setCompanyTheme,
        private readonly ShowCompany $showCompany,
        private readonly ToggleCompanyField $toggleCompanyField,
        private readonly UpdateCompany $updateCompany,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            CompanyResource::collection($this->listCompanies->execute()),
            Response::HTTP_OK,
        );
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->createCompany->execute($request->validated());

        return response()->json(
            CompanyResource::make($company),
            Response::HTTP_CREATED,
        );
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(
            CompanyResource::make($this->showCompany->execute($company)),
            Response::HTTP_OK,
        );
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company = $this->updateCompany->execute($company, $request->validated());

        return response()->json(CompanyResource::make($company), Response::HTTP_OK);
    }

    public function setTheme(Company $company, UpdateCompanyThemeRequest $request): JsonResponse
    {
        $company = $this->setCompanyTheme->execute($company, (int) $request->integer('theme_id'));

        return response()->json(CompanyResource::make($company), Response::HTTP_OK);
    }

    public function setImageAsset(Company $company, UpdateCompanyImageAssetRequest $request): JsonResponse
    {
        $company = $this->setCompanyImageAsset->execute(
            $company,
            (int) $request->integer('image_id'),
            $request->string('type')->value(),
        );

        return response()->json(CompanyResource::make($company), Response::HTTP_OK);
    }

    public function clearImageAsset(Company $company, ClearCompanyImageAssetRequest $request): JsonResponse
    {
        $company = $this->clearCompanyImageAsset->execute(
            $company,
            $request->string('type')->value(),
        );

        return response()->json(CompanyResource::make($company), Response::HTTP_OK);
    }

    public function toggleActive(
        Company $company,
        ToggleCompanyStatusRequest $request,
    ): JsonResponse
    {
        $company = $this->toggleCompanyField->execute($company, 'is_active');

        return response()->json(CompanyResource::make($company), Response::HTTP_OK);
    }

    public function toggleMapOption(
        Company $company,
        ToggleCompanyStatusRequest $request,
    ): JsonResponse
    {
        $company = $this->toggleCompanyField->execute($company, 'enable_map');

        return response()->json(CompanyResource::make($company), Response::HTTP_OK);
    }

    public function toggleDocumentsOption(
        Company $company,
        ToggleCompanyStatusRequest $request,
    ): JsonResponse {
        $company = $this->toggleCompanyField->execute($company, 'enable_documents');

        return response()->json(CompanyResource::make($company), Response::HTTP_OK);
    }

    public function destroy(Company $company, DeleteCompanyRequest $request): JsonResponse
    {
        $company->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
