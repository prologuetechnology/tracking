<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permission:update') ?? false;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id ?? null;

        return [
            'name' => ['required', 'string', 'unique:permissions,name,'.$permissionId],
            'guard_name' => ['required', 'string'],
        ];
    }
}
