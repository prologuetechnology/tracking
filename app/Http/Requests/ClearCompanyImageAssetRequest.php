<?php

namespace App\Http\Requests;

use App\Actions\Companies\SetCompanyImageAsset;
use Illuminate\Foundation\Http\FormRequest;

class ClearCompanyImageAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'in:'.implode(',', SetCompanyImageAsset::TYPES),
            ],
        ];
    }
}
