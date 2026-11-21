<?php

namespace App\Http\Requests;

class PreviewDesignDraftRequest extends SaveDesignDraftRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['required', 'string', 'max:128'],
            'draft_uuid' => ['nullable', 'uuid'],
        ];
    }
}
