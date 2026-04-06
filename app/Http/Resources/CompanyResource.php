<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'is_active' => (bool) $this->is_active,
            'name' => $this->name,
            'website' => $this->website,
            'phone' => $this->phone,
            'email' => $this->email,
            'pipeline_company_id' => $this->pipeline_company_id,
            'logo_image_id' => $this->logo_image_id,
            'banner_image_id' => $this->banner_image_id,
            'footer_image_id' => $this->footer_image_id,
            'theme_id' => $this->theme_id,
            'enable_map' => (bool) $this->enable_map,
            'enable_documents' => (bool) $this->enable_documents,
            'requires_brand' => (bool) $this->requires_brand,
            'brand' => $this->brand,
            'logo' => $this->whenLoaded(
                'logo',
                fn () => ImageResource::make($this->logo)->resolve(),
            ),
            'banner' => $this->whenLoaded(
                'banner',
                fn () => ImageResource::make($this->banner)->resolve(),
            ),
            'footer' => $this->whenLoaded(
                'footer',
                fn () => ImageResource::make($this->footer)->resolve(),
            ),
            'theme' => $this->whenLoaded(
                'theme',
                fn () => ThemeResource::make($this->theme)->resolve(),
            ),
            'api_token' => $this->whenLoaded(
                'apiToken',
                fn () => CompanyApiTokenResource::make($this->apiToken)->resolve(),
            ),
            'features' => $this->whenLoaded(
                'features',
                fn () => CompanyFeatureResource::collection($this->features)->resolve(),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
