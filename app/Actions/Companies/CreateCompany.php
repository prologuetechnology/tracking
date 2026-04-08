<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

class CreateCompany
{
    private readonly EnforceCompanyBrandGroup $enforceCompanyBrandGroup;

    public function __construct(?EnforceCompanyBrandGroup $enforceCompanyBrandGroup = null)
    {
        $this->enforceCompanyBrandGroup = $enforceCompanyBrandGroup ?? new EnforceCompanyBrandGroup;
    }

    public function execute(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes): Company {
            $siblingBrandAssignments = $attributes['sibling_brand_assignments'] ?? [];
            unset($attributes['sibling_brand_assignments']);

            $attributes['brand'] = Company::normalizeBrand($attributes['brand'] ?? null);

            $company = new Company($attributes);

            if (! array_key_exists('theme_id', $attributes)) {
                $company->theme_id = Theme::query()->value('id');
            }

            $company->save();

            return $this->enforceCompanyBrandGroup->execute($company, $siblingBrandAssignments);
        });
    }
}
