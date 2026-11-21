<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PreviewDesignDraftRequest extends SaveDesignDraftRequest
{
    public function rules(): array
    {
        $preview = [
            'preview_mode' => ['sometimes', 'required', Rule::in(['draft', 'live'])],
            'snapshot_token' => ['nullable', 'uuid'],
            'search_option' => ['required', Rule::in(['bol', 'carrierPro'])],
            'tracking_number' => ['required', 'string', 'max:128'],
        ];

        if ($this->input('preview_mode') === 'live') {
            return $preview;
        }

        return [
            ...parent::rules(),
            ...$preview,
            'name' => ['nullable', 'string', 'max:120'],
            'draft_uuid' => ['nullable', 'uuid'],
        ];
    }
}
