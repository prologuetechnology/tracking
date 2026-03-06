<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'pipeline_company_id' => ['required', 'integer', 'unique:companies,pipeline_company_id,'.$companyId],
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
            'requires_brand' => ['boolean'],
            'brand' => ['nullable', 'string', 'max:255', 'required_if:requires_brand,true'],
        ];
    }
}
