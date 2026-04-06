<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Support\Testing\FakePipelinePayloads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FakePipelineController extends Controller
{
    public function shipmentSearch(Request $request): JsonResponse
    {
        $trackingNumber = (string) $request->input('trackNum', 'BOL123');

        if (in_array($trackingNumber, ['BOL404', 'NOTFOUND'], true)) {
            return response()->json(['data' => []], 200);
        }

        $companyId = (int) $request->input('companyId', 1001);

        return response()->json(
            FakePipelinePayloads::shipmentSearch($trackingNumber, $companyId),
            200,
        );
    }

    public function shipmentDocuments(Request $request): JsonResponse
    {
        $trackingNumber = (string) data_get($request->input('Request'), 'BOLNumber', 'BOL123');

        return response()->json(
            FakePipelinePayloads::shipmentDocuments(config('app.url'), $trackingNumber),
            200,
        );
    }

    public function shipmentCoordinates(Request $request): JsonResponse
    {
        $route = (string) $request->query('r', '');

        if (! str_starts_with($route, 'mapApi/getRoutes')) {
            return response()->json([], 404);
        }

        return response()->json(FakePipelinePayloads::shipmentCoordinates(), 200);
    }

    public function document(string $filename): Response
    {
        return response('%PDF-1.4 fake-pdf', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => '1024',
            'Last-Modified' => 'Wed, 06 Mar 2024 12:00:00 GMT',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
