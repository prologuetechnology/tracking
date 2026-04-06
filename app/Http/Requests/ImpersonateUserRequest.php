<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImpersonateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user:impersonate') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
