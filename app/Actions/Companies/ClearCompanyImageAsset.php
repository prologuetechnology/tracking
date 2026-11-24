<?php

namespace App\Actions\Companies;

use App\Models\Company;

class ClearCompanyImageAsset
{
    public function execute(Company $company, string $type): Company
    {
        return (new SetCompanyImageAsset)->execute($company, null, $type);
    }
}
