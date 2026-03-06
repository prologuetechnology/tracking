<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllowedDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('allowed_domain:update') ?? false;
    }

    public function rules(): array
    {
        $allowedDomainId = $this->route('allowedDomain')?->id ?? null;

        return [
            'domain' => ['required', 'string', 'max:255', 'unique:allowed_domains,domain,'.$allowedDomainId],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
