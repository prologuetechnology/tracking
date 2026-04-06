<?php

namespace App\Actions\AllowedDomains;

use App\Models\AllowedDomain;
use App\Models\User;

class CreateAllowedDomain
{
    public function execute(array $attributes, User $user): AllowedDomain
    {
        return AllowedDomain::create([
            ...$attributes,
            'favicon_url' => $this->faviconUrl($attributes['domain']),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function faviconUrl(string $domain): string
    {
        return "https://www.google.com/s2/favicons?domain={$domain}";
    }
}
