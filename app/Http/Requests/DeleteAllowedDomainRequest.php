<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAllowedDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('allowed_domain:destroy') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
