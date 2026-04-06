<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('image:show') ?? false;
    }

    public function rules(): array
    {
        return [
            'image_type_id' => ['nullable', 'integer', 'exists:image_types,id'],
        ];
    }
}
