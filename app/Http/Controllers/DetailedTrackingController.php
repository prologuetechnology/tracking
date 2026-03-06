<?php

namespace App\Http\Controllers;

use App\Actions\Tracking\ResolveTrackingPayload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DetailedTrackingController extends Controller
{
    public function __construct(
        private readonly ResolveTrackingPayload $resolveTrackingPayload,
    ) {
    }

    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        $trackingNumber = $request->string('trackingNumber')->value();
        $searchOption = $request->string('searchOption')->value();
        $companyId = $request->query('companyId');
        $brand = $request->query('brand') ? strtoupper($request->query('brand')) : null;

        $payload = $this->resolveTrackingPayload->execute(
            trackingNumber: $trackingNumber,
            searchOption: $searchOption,
            companyId: $companyId,
            brand: $brand,
        );

        if (! $payload['found']) {
            return redirect(route('trackShipment.notFound', $trackingNumber));
        }

        return Inertia::render('brandedTracking/Index', [
            'initialTrackingData' => $payload['trackingData'],
            'initialCompany' => $payload['company']
                ? \App\Http\Resources\CompanyResource::make($payload['company'])->resolve()
                : null,
            'initialShipmentCoordinates' => $payload['shipmentCoordinates'],
            'initialShipmentDocuments' => $payload['shipmentDocuments'],
        ]);
    }

    public function trackingDataNotFound(string $trackingNumber): InertiaResponse
    {
        return Inertia::render('brandedTracking/Error', [
            'trackingNumber' => $trackingNumber,
        ]);
    }
}
