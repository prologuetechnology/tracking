<?php

namespace App\Actions\Companies;

use App\Models\Company;

class ToggleCompanyField
{
    public function execute(Company $company, string $field): Company
    {
        $company->forceFill([
            $field => ! (bool) $company->getAttribute($field),
        ])->save();

        return $company->load(ListCompanies::RELATIONS);
    }
}
