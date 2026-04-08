<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'pipeline_company_id' => ['required', 'integer'],
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
            'requires_brand' => ['boolean'],
            'brand' => ['nullable', 'string', 'max:255', 'required_if:requires_brand,true'],
            'sibling_brand_assignments' => ['nullable', 'array'],
            'sibling_brand_assignments.*.company_id' => ['required', 'integer', 'exists:companies,id'],
            'sibling_brand_assignments.*.brand' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignments = collect($this->input('sibling_brand_assignments', []))
            ->filter(fn (mixed $assignment): bool => is_array($assignment))
            ->map(fn (array $assignment): array => [
                'company_id' => $assignment['company_id'] ?? null,
                'brand' => Company::normalizeBrand(
                    is_string($assignment['brand'] ?? null) ? $assignment['brand'] : null,
                ),
            ])
            ->values()
            ->all();

        $this->merge([
            'brand' => Company::normalizeBrand(
                is_string($this->input('brand')) ? $this->input('brand') : null,
            ),
            'sibling_brand_assignments' => $assignments,
        ]);
    }
}
