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
            'upgrade_presentation' => ['sometimes', 'boolean'],
            'source_publication_id' => [Rule::prohibitedIf($this->route('draft') !== null || $this->filled('draft_uuid')), 'nullable', 'integer', 'min:1'],
            'layout' => ['sometimes', 'required', Rule::in(array_keys(DesignCatalog::LAYOUTS))],
            'emphasis' => ['sometimes', 'required', Rule::in(['delivery', 'event'])],
            'palette' => ['sometimes', 'required', Rule::in(array_keys(DesignCatalog::PALETTES))],
            'headline_style' => ['sometimes', 'required', Rule::in(['layout', 'sans', 'serif'])],
            'custom_colors' => [Rule::requiredIf($this->input('palette') === 'custom'), 'array:brand,accent,paper', 'required_array_keys:brand,accent,paper'],
            'custom_colors.*' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'section_orders' => ['sometimes', 'bail', 'array', $this->moduleLists(fullOrder: true)],
            'hidden_modules' => ['sometimes', 'bail', 'array', $this->moduleLists(fullOrder: false)],
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

    private function moduleLists(bool $fullOrder): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($fullOrder) {
            $layouts = DesignCatalog::orders();
            // Catalog layout IDs contain literal dots. Read the original keys before validation escaping.
            foreach ($this->input($attribute, []) as $layout => $modules) {
                if (! isset($layouts[$layout]) || ! is_array($modules) || ! array_is_list($modules)) {
                    $fail('Choose a supported layout and list of sections.');

                    return;
                }
                $remaining = DesignCatalog::MODULES;
                foreach ($modules as $module) {
                    if (! is_string($module) || ! isset($remaining[$module])) {
                        $fail('Each supporting section may appear only once.');

                        return;
                    }
                    unset($remaining[$module]);
                }
                if ($fullOrder && $remaining) {
                    $fail('Include every supporting section in the layout order.');

                    return;
                }
            }
        };
    }
}
