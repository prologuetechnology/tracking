<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('theme:store') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:themes,name',
            'primary_hue' => 'required|array',
            'primary_hue.*' => 'integer|min:0|max:360',
            'primary_saturation' => 'required|array',
            'primary_saturation.*' => 'integer|min:0|max:100',
            'primary_lightness' => 'required|array',
            'primary_lightness.*' => 'integer|min:0|max:100',
            'accent_hue' => 'required|array',
            'accent_hue.*' => 'integer|min:0|max:360',
            'accent_saturation' => 'required|array',
            'accent_saturation.*' => 'integer|min:0|max:100',
            'accent_lightness' => 'required|array',
            'accent_lightness.*' => 'integer|min:0|max:100',
            'derive_from' => 'required|string|in:primary,accent',
            'radius' => 'nullable|string|max:255',
            'is_system' => 'boolean',
        ];
    }
}
