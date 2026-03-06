<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:destroy') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
