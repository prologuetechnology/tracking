<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTrackingStatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'trackingNumber' => 'required|string|max:255',
            'searchOption' => 'required|string|in:bol,carrierPro',
            'companyId' => 'nullable|integer',
            'zipCode' => 'nullable|string|max:10',
        ];
    }
}
