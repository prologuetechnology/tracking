<?php

namespace App\Actions\Companies;

use App\Enums\ImageTypeEnum;
use App\Models\Company;
use InvalidArgumentException;

class SetCompanyImageAsset
{
    public function execute(
        Company $company,
        int $imageId,
        string $type,
    ): Company {
        match ($type) {
            ImageTypeEnum::LOGO->value => $company->forceFill([
                'logo_image_id' => $imageId,
            ]),
            ImageTypeEnum::BANNER->value => $company->forceFill([
                'banner_image_id' => $imageId,
            ]),
            ImageTypeEnum::FOOTER->value => $company->forceFill([
                'footer_image_id' => $imageId,
            ]),
            default => throw new InvalidArgumentException('Invalid image type.'),
        };

        $company->save();

        return $company->load(ListCompanies::RELATIONS);
    }
}
