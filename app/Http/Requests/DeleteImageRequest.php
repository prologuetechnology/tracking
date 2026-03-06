<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('image:destroy') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
