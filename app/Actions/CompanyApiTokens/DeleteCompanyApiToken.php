<?php

namespace App\Actions\CompanyApiTokens;

use App\Models\CompanyApiToken;

class DeleteCompanyApiToken
{
    public function execute(CompanyApiToken $companyApiToken): void
    {
        $companyApiToken->delete();
    }
}
