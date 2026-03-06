<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('role:destroy') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
