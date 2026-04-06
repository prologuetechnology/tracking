<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'colors' => $this->colors,
            'radius' => $this->radius,
            'is_system' => (bool) $this->is_system,
            'derive_from' => $this->derive_from,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
