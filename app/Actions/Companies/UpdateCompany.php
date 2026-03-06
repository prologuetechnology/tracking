<?php

namespace App\Actions\Companies;

use App\Models\Company;

class UpdateCompany
{
    public function execute(Company $company, array $attributes): Company
    {
        $company->update($attributes);

        return $company->load(ListCompanies::RELATIONS);
    }
}
