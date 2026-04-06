<?php

namespace App\Actions\AllowedDomains;

use App\Models\AllowedDomain;
use App\Models\User;

class UpdateAllowedDomain
{
    public function execute(
        AllowedDomain $allowedDomain,
        array $attributes,
        User $user,
    ): AllowedDomain {
        $domain = $attributes['domain'] ?? $allowedDomain->domain;

        $allowedDomain->update([
            ...$attributes,
            'favicon_url' => $this->faviconUrl($domain),
            'updated_by' => $user->id,
        ]);

        return $allowedDomain->refresh();
    }

    private function faviconUrl(string $domain): string
    {
        return "https://www.google.com/s2/favicons?domain={$domain}";
    }
}
