<?php

namespace App\Http\Controllers\Api;

use App\Actions\CompanyApiTokens\CreateCompanyApiToken;
use App\Actions\CompanyApiTokens\DeleteCompanyApiToken;
use App\Actions\CompanyApiTokens\ValidateCompanyApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteCompanyApiTokenRequest;
use App\Http\Requests\StoreCompanyApiTokenRequest;
use App\Http\Requests\ValidateCompanyApiTokenRequest;
use App\Http\Resources\CompanyApiTokenResource;
use App\Models\Company;
use App\Models\CompanyApiToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyApiTokenController extends Controller
{
    public function __construct(
        private readonly CreateCompanyApiToken $createCompanyApiToken,
        private readonly DeleteCompanyApiToken $deleteCompanyApiToken,
        private readonly ValidateCompanyApiToken $validateCompanyApiToken,
    ) {
    }

    public function store(StoreCompanyApiTokenRequest $request): JsonResponse
    {
        $company = Company::query()->findOrFail($request->integer('company_id'));
        $companyApiToken = $this->createCompanyApiToken->execute(
            $company,
            $request->validated(),
        );

        return response()->json(
            CompanyApiTokenResource::make($companyApiToken)->resolve(),
            Response::HTTP_CREATED,
        );
    }

    public function validateToken(ValidateCompanyApiTokenRequest $request, Company $company): JsonResponse
    {
        $companyApiToken = $this->validateCompanyApiToken->execute($company);

        return response()->json(
            CompanyApiTokenResource::make($companyApiToken)->resolve(),
            Response::HTTP_OK,
        );
    }

    public function destroy(
        CompanyApiToken $companyApiToken,
        DeleteCompanyApiTokenRequest $request,
    ): JsonResponse
    {
        $this->deleteCompanyApiToken->execute($companyApiToken);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
