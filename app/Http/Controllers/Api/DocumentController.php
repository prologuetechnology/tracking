<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tracking\GetShipmentDocuments;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetShipmentDocumentsRequest;
use App\Models\Company;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(
        private readonly GetShipmentDocuments $getShipmentDocuments,
    ) {
    }

    public function shipmentDocuments(GetShipmentDocumentsRequest $request): JsonResponse
    {
        $company = Company::query()
            ->where('pipeline_company_id', $request->validated('companyId'))
            ->with('apiToken')
            ->firstOrFail();

        $documents = $this->getShipmentDocuments->execute(
            $company,
            $request->validated('trackingNumber'),
        );

        return response()->json([
            'shipmentDocuments' => $documents,
        ], Response::HTTP_OK);
    }
}
