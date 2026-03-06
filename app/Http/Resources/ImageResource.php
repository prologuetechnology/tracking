<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'image_type_id' => $this->image_type_id,
            'file_path' => $this->file_path,
            'image_type' => $this->whenLoaded(
                'imageType',
                fn () => ImageTypeResource::make($this->imageType)->resolve(),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
