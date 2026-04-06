<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentCoordinateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->normalize($this->resource);
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->normalize(...), $value);
        }

        if (is_object($value)) {
            return array_map($this->normalize(...), (array) $value);
        }

        return $value;
    }
}
