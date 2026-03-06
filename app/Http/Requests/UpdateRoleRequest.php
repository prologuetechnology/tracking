<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('role:update') ?? false;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? null;

        return [
            'name' => ['required', 'string', 'unique:roles,name,'.$roleId],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ];
    }
}
