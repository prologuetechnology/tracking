<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ];
    }
}
