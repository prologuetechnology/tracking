<?php

namespace App\Http\Requests;

use App\Actions\DesignDrafts\ClassicDesign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDesignDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company:update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'theme_id' => ['present', 'nullable', 'integer', 'min:1'],
            'logo_image_id' => ['present', 'nullable', 'integer', 'min:1'],
            'banner_image_id' => ['present', 'nullable', 'integer', 'min:1'],
            'footer_image_id' => ['present', 'nullable', 'integer', 'min:1'],
            'sections' => ['present', 'array', 'list'],
            'sections.*' => ['string', 'distinct', Rule::in(array_keys(ClassicDesign::SECTIONS))],
            'search_option' => ['required', Rule::in(['bol', 'carrierPro'])],
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'base_version' => [Rule::requiredIf($this->route('draft') !== null), 'nullable', 'integer', 'min:1'],
        ];
    }
}
