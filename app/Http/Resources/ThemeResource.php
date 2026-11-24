<?php

namespace App\Http\Resources;

use App\Actions\Themes\ThemeUsage;
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
            'style' => $this->style,
            'version' => $this->whenLoaded('latestVersion', fn () => $this->latestVersion->version),
            'theme_version_id' => $this->whenLoaded('latestVersion', fn () => $this->latestVersion->id),
            'versions' => $this->whenLoaded('versions', fn () => $this->versions->map(fn ($version) => $version->only(['id', 'version', 'name', 'colors', 'radius', 'style', 'created_at']))),
            'usage' => $this->when($this->resource->relationLoaded('versions') && $request->user()?->can('company:show'),
                fn () => ThemeUsage::forTheme($this->resource, $request->user()->can('company:update'))),
            'is_system' => (bool) $this->is_system,
            'derive_from' => $this->derive_from,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
