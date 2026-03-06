<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\Theme;

class CreateCompany
{
    public function execute(array $attributes): Company
    {
        $company = new Company($attributes);

        if (! array_key_exists('theme_id', $attributes)) {
            $company->theme_id = Theme::query()->value('id');
        }

        $company->save();

        return $company->load(ListCompanies::RELATIONS);
    }
}
