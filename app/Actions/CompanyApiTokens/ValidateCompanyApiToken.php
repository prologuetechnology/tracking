<?php

namespace App\Actions\CompanyApiTokens;

use App\Models\Company;
use App\Models\CompanyApiToken;
use App\Services\Pipeline\PipelineApiShipmentSearch;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateCompanyApiToken
{
    public function execute(Company $company): CompanyApiToken
    {
        $company->loadMissing('apiToken');

        if ($company->apiToken === null) {
            throw new NotFoundHttpException('Company does not have an API token.');
        }

        $isValid = $this->tokenMatchesCompany($company, $company->apiToken);

        $company->apiToken->forceFill([
            'is_valid' => $isValid,
        ])->save();

        return $company->apiToken->refresh();
    }

    private function tokenMatchesCompany(Company $company, CompanyApiToken $companyApiToken): bool
    {
        $shipmentSearchClient = new PipelineApiShipmentSearch($companyApiToken->api_token);

        $response = $shipmentSearchClient->searchShipment(
            trackingNumber: $companyApiToken->bol,
            searchOption: 'bol',
            globalSearch: false,
        );

        if ($response->failed()) {
            return false;
        }

        $responseCompanyId = (int) data_get($response->json(), 'data.0.companyId');

        return $responseCompanyId !== 0
            && $responseCompanyId === (int) $company->pipeline_company_id;
    }
}
