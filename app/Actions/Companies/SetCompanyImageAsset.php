<?php

namespace App\Actions\Companies;

use App\Enums\ImageTypeEnum;
use App\Models\Company;
use InvalidArgumentException;

class SetCompanyImageAsset
{
    public const TYPES = [
        ImageTypeEnum::LOGO->value,
        ImageTypeEnum::BANNER->value,
        ImageTypeEnum::FOOTER->value,
    ];

    public function execute(
        Company $company,
        ?int $imageId,
        string $type,
    ): Company {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Invalid image type.');
        }

        PreserveCompanyBranding::operationalAttributes($company, [$type.'_image_id' => $imageId]);

        return $company->load(ListCompanies::RELATIONS);
    }
}
