<?php

namespace App\Actions\Images;

use App\Models\ImageType;
use Illuminate\Database\Eloquent\Collection;

class ListImageTypes
{
    public function execute(): Collection
    {
        return ImageType::query()
            ->orderBy('id')
            ->get();
    }
}
