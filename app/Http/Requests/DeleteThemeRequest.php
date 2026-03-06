<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('theme:destroy') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
