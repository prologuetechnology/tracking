<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('image:store') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image_type_id' => ['required', 'exists:image_types,id'],
            'image' => ['required', File::image(allowSvg: true)->max(2048)],
        ];
    }
}
