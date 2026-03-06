<?php

namespace App\Actions\Themes;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Collection;

class ListThemes
{
    public function execute(): Collection
    {
        return Theme::query()
            ->orderBy('name')
            ->get();
    }
}
