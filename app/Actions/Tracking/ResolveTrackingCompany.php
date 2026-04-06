<?php

namespace App\Actions\Tracking;

use App\Models\Company;

class ResolveTrackingCompany
{
    public function execute(
        ?string $brand = null,
        ?int $companyId = null,
        ?int $pipelineCompanyId = null,
    ): ?Company {
        return Company::findByIdentifier($brand, $companyId, $pipelineCompanyId);
    }
}
