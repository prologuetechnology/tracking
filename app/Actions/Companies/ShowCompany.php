<?php

namespace App\Actions\Companies;

use App\Models\Company;

class ShowCompany
{
    public function execute(Company $company): Company
    {
        return $company->load(ListCompanies::RELATIONS);
    }
}
