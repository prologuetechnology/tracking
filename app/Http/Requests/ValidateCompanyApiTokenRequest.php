<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateCompanyApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:show') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
