<?php

namespace App\Actions\CompanyApiTokens;

use App\Models\Company;
use App\Models\CompanyApiToken;
use App\Services\Pipeline\PipelineApiShipmentSearch;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CreateCompanyApiToken
{
    public function execute(Company $company, array $attributes): CompanyApiToken
    {
        $company->loadMissing('apiToken');

        if ($company->apiToken !== null) {
            throw new ConflictHttpException('Company already has api token.');
        }

        $isValid = $this->tokenMatchesCompany(
            company: $company,
            apiToken: $attributes['api_token'],
            trackingNumber: $attributes['trackingNumber'],
        );

        if (! $isValid) {
            throw new UnauthorizedHttpException('', 'Invalid API token.');
        }

        return CompanyApiToken::query()->create([
            'company_id' => $company->id,
            'api_token' => $attributes['api_token'],
            'bol' => $attributes['trackingNumber'],
            'is_valid' => true,
        ]);
    }

    private function tokenMatchesCompany(
        Company $company,
        string $apiToken,
        string $trackingNumber,
    ): bool {
        $shipmentSearchClient = new PipelineApiShipmentSearch($apiToken);

        $response = $shipmentSearchClient->searchShipment(
            trackingNumber: $trackingNumber,
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
