<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\ClassicDesign;
use App\Actions\Tracking\ResolveTrackingPayload;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\DesignDraftRevision;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DesignDraftPreviewController extends Controller
{
    public function show(Company $company, DesignDraft $draft, DesignDraftRevision $revision): Response
    {
        return Inertia::render('admin/designDrafts/Preview', [
            'company' => $company->only(['uuid', 'name']),
            'draft' => $draft->only(['uuid', 'name']),
            'revision' => $revision->only(['version', 'search_option', 'tracking_number']),
        ]);
    }

    public function render(Company $company, DesignDraft $draft, DesignDraftRevision $revision, Request $request, ResolveTrackingPayload $resolver): Response
    {
        $lookup = validator($request->query(), [
            'trackingNumber' => ['required', 'string', 'max:128'],
            'searchOption' => ['required', Rule::in(['bol', 'carrierPro'])],
        ]);
        $design = $revision->design;
        abort_unless($design['layout'] === ClassicDesign::LAYOUT, 404);
        if ($lookup->fails()) {
            return Inertia::render('admin/designDrafts/Unavailable');
        }
        $payload = $resolver->execute(
            trackingNumber: $lookup->validated()['trackingNumber'],
            searchOption: $lookup->validated()['searchOption'],
            companyId: (string) $company->pipeline_company_id,
            brand: $company->brand,
            expectedCompany: $company,
            visibleSections: $design['sections'],
        );
        if (! $payload['found']) {
            return Inertia::render('admin/designDrafts/Unavailable');
        }

        $visible = array_intersect($design['sections'], ClassicDesign::availableSections($payload['company']));
        $tracking = $payload['trackingData'];
        if (! in_array('special_instructions', $visible, true)) {
            unset($tracking['specialInstructions']);
        }
        if (! in_array('customer_pos', $visible, true)) {
            unset($tracking['poNumbers']);
        }

        return Inertia::render('brandedTracking/Classic', [
            'isDraftPreview' => true,
            'initialCompany' => [
                'name' => $company->name,
                'logo' => $design['logo'],
                'banner' => $design['banner'],
                'footer' => $design['footer'],
                'theme' => $design['theme'],
                'features' => array_values(array_map(fn ($slug) => ['slug' => $slug], $visible)),
            ],
            'initialTrackingData' => $tracking,
            'initialShipmentCoordinates' => $payload['shipmentCoordinates'],
            'initialShipmentDocuments' => $payload['shipmentDocuments'],
        ]);
    }
}
