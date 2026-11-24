<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->session()->has('impersonate_original_id');
    }

    public function rules(): array
    {
        return ['appearance' => ['required', 'string', Rule::in(['system', 'light', 'dark'])]];
    }
}
