<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImageTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyLogoRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Requests\UpdateCompanyThemeRequest;
use App\Models\Company;
use App\Models\Theme;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $companies = Company::with(['logo', 'theme', 'features'])->get();

        return response()->json($companies, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        // Get the first theme to assign to the company if no theme_id is provided.
        $theme = Theme::first();

        $company = new Company($request->validated());

        // If no theme_id is provided, assign the first theme to the company.
        if (! $request->has('theme_id')) {
            $company->theme_id = $theme ? $theme->id : null;
        }

        $company->save();

        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): JsonResponse
    {
        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_OK);
    }

    /**
     * Set the company's theme.
     */
    public function setTheme(Company $company, UpdateCompanyThemeRequest $request): JsonResponse
    {
        $company->theme_id = $request->theme_id;

        $company->save();

        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_OK);
    }

    public function setImageAsset(Company $company, UpdateCompanyLogoRequest $request): JsonResponse
    {
        switch ($request->type) {
            case ImageTypeEnum::LOGO->value:
                if ($company->logo) {
                    $company->logo->delete();
                }
                $company->logo_image_id = $request->image_id;
                break;

            case ImageTypeEnum::BANNER->value:
                if ($company->banner) {
                    $company->banner->delete();
                }
                $company->banner_image_id = $request->image_id;
                break;

            case ImageTypeEnum::FOOTER->value:
                if ($company->footer) {
                    $company->footer->delete();
                }
                $company->footer_image_id = $request->image_id;
                break;

            default:
                return response()->json(['error' => 'Invalid image type'], Response::HTTP_BAD_REQUEST);
        }

        $company->save();

        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_OK);
    }

    /**
     * Toggle company is active field.
     */
    public function toggleActive(Company $company): JsonResponse
    {
        $company->is_active = ! $company->is_active;

        $company->save();

        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_OK);
    }

    /**
     * Toggle a one of the company's boolean fields.
     */
    public function toggleMapOption(Company $company): JsonResponse
    {
        $company->enable_map = ! $company->enable_map;

        $company->save();

        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_OK);
    }

    /**
     * Toggle documents are enabled field.
     */
    public function toggleDocumentsOption(Company $company): JsonResponse
    {
        $company->enable_documents = ! $company->enable_documents;

        $company->save();

        $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

        return response()->json($company, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
