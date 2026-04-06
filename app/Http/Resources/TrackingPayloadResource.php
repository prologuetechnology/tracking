<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingPayloadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'trackingData' => ShipmentTrackingResource::make($this['trackingData'])->resolve(),
            'company' => $this['company']
                ? CompanyResource::make($this['company'])->resolve()
                : null,
            'shipmentCoordinates' => ShipmentCoordinateResource::collection(
                collect($this['shipmentCoordinates'] ?? [])->values()->all(),
            )->resolve(),
            'shipmentDocuments' => ShipmentDocumentResource::collection(
                collect($this['shipmentDocuments'] ?? [])->values()->all(),
            )->resolve(),
        ];
    }
}
