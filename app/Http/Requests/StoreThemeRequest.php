<?php

namespace App\Http\Requests;

use App\Actions\Themes\ResolveThemeStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('theme:store') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('themes', 'name')->ignore($this->route('theme')?->id)],
            'is_system' => ['sometimes', 'boolean'],
        ];
        if ($this->has('style')) {
            return [...$rules, ...ResolveThemeStyle::rules()];
        }
        if ($this->route('theme') && $this->filled('source_version_id')) {
            return $rules;
        }

        return [
            ...$rules,
            'primary_hue' => 'required|array|list|min:1',
            'primary_hue.*' => 'integer|min:0|max:360',
            'primary_saturation' => 'required|array|list|min:1',
            'primary_saturation.*' => 'integer|min:0|max:100',
            'primary_lightness' => 'required|array|list|min:1',
            'primary_lightness.*' => 'integer|min:0|max:100',
            'accent_hue' => 'required|array|list|min:1',
            'accent_hue.*' => 'integer|min:0|max:360',
            'accent_saturation' => 'required|array|list|min:1',
            'accent_saturation.*' => 'integer|min:0|max:100',
            'accent_lightness' => 'required|array|list|min:1',
            'accent_lightness.*' => 'integer|min:0|max:100',
            'derive_from' => 'required|string|in:primary,accent',
            'radius' => 'nullable|string|max:255',
        ];
    }
}
