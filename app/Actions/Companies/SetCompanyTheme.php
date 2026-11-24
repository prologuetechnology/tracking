<?php

namespace App\Actions\Companies;

use App\Models\Company;

class SetCompanyTheme
{
    public function execute(Company $company, int $themeId): Company
    {
        PreserveCompanyBranding::operationalAttributes($company, ['theme_id' => $themeId]);

        return $company->load(ListCompanies::RELATIONS);
    }
}
