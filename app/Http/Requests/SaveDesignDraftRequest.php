<?php

namespace App\Http\Requests;

use App\Actions\DesignDrafts\ClassicDesign;
use App\Actions\DesignDrafts\DesignCatalog;
use Closure;
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
            'source_publication_id' => [Rule::prohibitedIf($this->route('draft') !== null || $this->filled('draft_uuid')), 'nullable', 'integer', 'min:1'],
            'layout' => ['sometimes', 'required', Rule::in(array_keys(DesignCatalog::LAYOUTS))],
            'emphasis' => ['sometimes', 'required', Rule::in(['delivery', 'event'])],
            'palette' => ['sometimes', 'required', Rule::in(array_keys(DesignCatalog::PALETTES))],
            'headline_style' => ['sometimes', 'required', Rule::in(['layout', 'sans', 'serif'])],
            'section_orders' => ['sometimes', 'bail', 'array', function (string $attribute, mixed $value, Closure $fail) {
                $allowed = DesignCatalog::orders();
                $expected = array_keys(DesignCatalog::MODULES);
                sort($expected);
                foreach ($this->input('section_orders', []) as $layout => $order) {
                    if (! isset($allowed[$layout]) || ! is_array($order) || ! array_is_list($order)) {
                        $fail('Choose a valid layout and section order.');

                        return;
                    }
                    sort($order);
                    if ($order !== $expected) {
                        $fail('Include every supporting section exactly once.');

                        return;
                    }
                }
            }],
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
