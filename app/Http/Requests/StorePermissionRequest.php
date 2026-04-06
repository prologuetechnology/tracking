<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permission:store') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'unique:permissions,name'],
            'guard_name' => ['required', 'string'],
        ];
    }
}
