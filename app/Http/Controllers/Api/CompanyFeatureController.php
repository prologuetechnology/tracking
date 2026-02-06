<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncCompanyFeaturesRequest;
use App\Models\Company;
use App\Models\CompanyFeature;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyFeatureController extends Controller
{
    public function index(): JsonResponse
    {
        $features = CompanyFeature::query()
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($features, Response::HTTP_OK);
    }

    public function show(Company $company): JsonResponse
    {
        $company->load('features');

        return response()->json($company->features, Response::HTTP_OK);
    }

    public function sync(Company $company, SyncCompanyFeaturesRequest $request): JsonResponse
    {
        $featureSlugs = $request->validated('features', []);

        $company->syncFeatures($featureSlugs);
        $company->syncLegacyFeatureColumns($featureSlugs);
        $company->load('features');

        return response()->json($company, Response::HTTP_OK);
    }

    public function toggle(Company $company, CompanyFeature $feature): JsonResponse
    {
        $isEnabled = $company->features()
            ->whereKey($feature->id)
            ->exists();

        if ($isEnabled) {
            $company->features()->detach($feature->id);
        } else {
            $company->features()->syncWithoutDetaching([$feature->id]);
        }

        $company->syncLegacyFeatureColumn($feature->slug, ! $isEnabled);

        $company->load('features');

        return response()->json($company, Response::HTTP_OK);
    }
}
