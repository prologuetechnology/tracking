<?php

namespace App\Http\Requests;

use App\Actions\DesignDrafts\PortalReviewShipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortalReviewStyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && (($user->can('company:show') && $user->can('company:update')) || ($this->isMethod('get') && $user->can('theme:show')));
    }

    public function rules(): array
    {
        $hex = ['required', 'string', 'regex:/^#[0-9a-f]{6}$/i'];

        return [
            'colors' => ['required', 'array:brand,accent,paper'],
            'colors.brand' => $hex, 'colors.accent' => $hex, 'colors.paper' => $hex,
            'overrides' => ['sometimes', 'array:'.implode(',', PortalReviewShipment::OVERRIDES)],
            'overrides.*' => $hex,
            'headline' => ['required', Rule::in(['layout', 'serif', 'sans'])],
            'corners' => ['required', Rule::in(['square', 'soft', 'round'])],
            'banner' => ['required', Rule::in(['jayco-overlook', 'canyon'])],
        ];
    }
}
