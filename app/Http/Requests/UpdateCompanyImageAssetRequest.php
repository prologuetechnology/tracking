<?php

namespace App\Http\Requests;

use App\Actions\Companies\SetCompanyImageAsset;
use App\Models\ImageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rule;

class UpdateCompanyImageAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        $type = $this->string('type')->value();
        $imageTypeId = ImageType::query()
            ->where('name', $type)
            ->value('id');

        return [
            'image_id' => [
                'required',
                Rule::exists('images', 'id')->when(
                    $imageTypeId !== null,
                    fn (Exists $rule) => $rule->where(
                        fn (Builder $query) => $query->where('image_type_id', $imageTypeId),
                    ),
                ),
            ],
            'type' => [
                'required',
                'string',
                'in:'.implode(',', SetCompanyImageAsset::TYPES),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image_id.exists' => 'The selected image must match the requested asset type.',
        ];
    }
}
