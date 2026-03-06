<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'api_token' => ['required', 'string'],
            'trackingNumber' => ['required', 'string'],
        ];
    }
}
