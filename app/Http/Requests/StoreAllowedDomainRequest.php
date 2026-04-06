<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAllowedDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('allowed_domain:store') ?? false;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', 'unique:allowed_domains,domain'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
