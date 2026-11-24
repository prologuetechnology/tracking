<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PreserveCompanyBranding
{
    public const FIELDS = ['theme_id', 'theme_version_id', 'logo_image_id', 'banner_image_id', 'footer_image_id'];

    /**
     * Legacy clients may echo unchanged settings while saving company details.
     * Never forward those keys to an update, including during a concurrent publication.
     */
    public static function operationalAttributes(Company $company, array $attributes): array
    {
        $errors = [];

        foreach (Arr::only($attributes, self::FIELDS) as $field => $value) {
            if ((string) $value !== (string) $company->getAttribute($field)) {
                $errors[$field] = 'Change branding in a design draft and publish its approved proof.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return Arr::except($attributes, self::FIELDS);
    }
}
