<?php

namespace App\Actions\AllowedDomains;

use App\Models\AllowedDomain;
use Illuminate\Database\Eloquent\Collection;

class ListAllowedDomains
{
    public function execute(): Collection
    {
        return AllowedDomain::query()
            ->orderBy('domain')
            ->get();
    }
}
