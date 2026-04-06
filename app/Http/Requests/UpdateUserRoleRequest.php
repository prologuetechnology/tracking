<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'role.name' => ['required', 'string', 'exists:roles,name'],
        ];
    }
}
