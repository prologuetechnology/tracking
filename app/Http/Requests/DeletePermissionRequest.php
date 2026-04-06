<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeletePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permission:destroy') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
