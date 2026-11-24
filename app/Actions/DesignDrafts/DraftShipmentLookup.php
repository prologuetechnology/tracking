<?php

namespace App\Actions\DesignDrafts;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DraftShipmentLookup
{
    /** @return array{trackingNumber: string, searchOption: string}|array{} */
    public static function fromQuery(Request $request): array
    {
        $check = validator($request->query(), [
            'trackingNumber' => ['required', 'string', 'max:255'],
            'searchOption' => ['required', 'string', Rule::in(['bol', 'carrierPro'])],
        ]);

        return $check->passes() ? $check->validated() : [];
    }
}
