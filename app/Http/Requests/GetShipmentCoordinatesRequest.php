<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetShipmentCoordinatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'trackingNumber' => ['required', 'string', 'max:255'],
            'pipelineCompanyId' => ['required', 'string', 'max:255'],
        ];
    }
}
