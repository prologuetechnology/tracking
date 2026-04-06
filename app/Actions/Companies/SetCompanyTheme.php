<?php

namespace App\Actions\Companies;

use App\Models\Company;

class SetCompanyTheme
{
    public function execute(Company $company, int $themeId): Company
    {
        $company->forceFill([
            'theme_id' => $themeId,
        ])->save();

        return $company->load(ListCompanies::RELATIONS);
    }
}
