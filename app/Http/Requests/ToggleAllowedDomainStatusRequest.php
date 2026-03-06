<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleAllowedDomainStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('allowed_domain:update') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
