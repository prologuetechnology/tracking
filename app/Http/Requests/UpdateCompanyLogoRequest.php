<?php

namespace App\Http\Requests;

use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'image_id' => 'required|exists:images,id',
            'type' => [
                'required',
                'string',
                'in:'.implode(',', ImageTypeEnum::values()),
            ],
        ];
    }
}
