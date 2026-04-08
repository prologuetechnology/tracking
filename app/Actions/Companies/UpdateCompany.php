<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class UpdateCompany
{
    private readonly EnforceCompanyBrandGroup $enforceCompanyBrandGroup;

    public function __construct(?EnforceCompanyBrandGroup $enforceCompanyBrandGroup = null)
    {
        $this->enforceCompanyBrandGroup = $enforceCompanyBrandGroup ?? new EnforceCompanyBrandGroup;
    }

    public function execute(Company $company, array $attributes): Company
    {
        return DB::transaction(function () use ($company, $attributes): Company {
            $siblingBrandAssignments = $attributes['sibling_brand_assignments'] ?? [];
            unset($attributes['sibling_brand_assignments']);

            $attributes['brand'] = Company::normalizeBrand($attributes['brand'] ?? null);

            $company->update($attributes);

            return $this->enforceCompanyBrandGroup->execute($company, $siblingBrandAssignments);
        });
    }
}
