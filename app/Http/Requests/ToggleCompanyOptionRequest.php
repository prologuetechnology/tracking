<?php

namespace App\Http\Requests;

use App\Rules\ValidCompanyBooleanField;
use Illuminate\Foundation\Http\FormRequest;

class ToggleCompanyOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'field' => ['required', 'string', new ValidCompanyBooleanField],
        ];
    }
}
