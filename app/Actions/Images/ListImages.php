<?php

namespace App\Actions\Images;

use App\Models\Image;
use Illuminate\Database\Eloquent\Collection;

class ListImages
{
    public function execute(?int $imageTypeId = null): Collection
    {
        return Image::query()
            ->with('imageType')
            ->withCount([
                'logoCompanies',
                'bannerCompanies',
                'footerCompanies',
            ])
            ->when($imageTypeId, fn ($query) => $query->where('image_type_id', $imageTypeId))
            ->orderByDesc('id')
            ->get();
    }
}
