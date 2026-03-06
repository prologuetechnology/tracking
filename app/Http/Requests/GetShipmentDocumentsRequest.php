<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetShipmentDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:show') ?? false;
    }

    public function rules(): array
    {
        return [
            'trackingNumber' => ['required', 'string', 'max:255'],
            'companyId' => ['required', 'integer', 'exists:companies,pipeline_company_id'],
        ];
    }
}
