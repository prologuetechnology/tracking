<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListImageTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('image:show') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
