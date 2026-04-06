<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('role:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'distinct'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
