<?php

namespace App\Actions\AllowedDomains;

use App\Models\AllowedDomain;

class ToggleAllowedDomainStatus
{
    public function execute(AllowedDomain $allowedDomain): AllowedDomain
    {
        $allowedDomain->forceFill([
            'is_active' => ! $allowedDomain->is_active,
        ])->save();

        return $allowedDomain->refresh();
    }
}
