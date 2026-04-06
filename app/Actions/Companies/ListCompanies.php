<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class ListCompanies
{
    public const RELATIONS = ['logo', 'banner', 'footer', 'theme', 'apiToken', 'features'];

    public function execute(): Collection
    {
        return Company::query()
            ->with(self::RELATIONS)
            ->orderBy('name')
            ->get();
    }
}
