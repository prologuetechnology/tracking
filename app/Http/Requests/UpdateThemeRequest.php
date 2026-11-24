<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateThemeRequest extends StoreThemeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('theme:update') ?? false;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'base_version' => ['required', 'integer', 'min:1'],
            'source_version_id' => ['sometimes', 'integer', 'min:1', Rule::prohibitedIf($this->has('style'))],
        ];
    }
}
