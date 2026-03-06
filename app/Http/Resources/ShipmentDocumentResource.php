<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => data_get($this->resource, 'name'),
            'url' => data_get($this->resource, 'url'),
            'type' => data_get($this->resource, 'type'),
            'size' => data_get($this->resource, 'size'),
            'last_modified' => data_get($this->resource, 'last_modified'),
            'error' => (bool) data_get($this->resource, 'error', false),
        ];
    }
}
