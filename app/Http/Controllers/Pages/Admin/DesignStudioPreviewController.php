<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\ClassicDesign;
use App\Actions\DesignDrafts\TrackingDesignPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewDesignDraftRequest;
use App\Models\Company;
use App\Models\DesignDraftRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DesignStudioPreviewController extends Controller
{
    public function __invoke(Company $company, PreviewDesignDraftRequest $request, ClassicDesign $designs, TrackingDesignPage $pages): JsonResponse
    {
        $input = $request->validated();
        $previous = null;
        if ($input['draft_uuid'] ?? null) {
            $draft = $company->drafts()->where('uuid', $input['draft_uuid'])->firstOrFail();
            $previous = $draft->revisions()->where('version', $input['base_version'] ?? 0)->firstOrFail();
        }
        $revision = new DesignDraftRevision(['design' => $designs->snapshot($company, $input, $previous)]);
        $page = $pages->preview($company, $revision, [
            'searchOption' => $input['search_option'], 'trackingNumber' => $input['tracking_number'],
        ]);
        if (! $page) {
            throw ValidationException::withMessages(['tracking_number' => 'No shipment was available for this company and reference.']);
        }

        // Mount a fresh Inertia application in the frame so the public theme, viewport
        // and navigation never touch the staff editor.
        $frame = Inertia::render($page['component'], [
            ...$page['props'], 'isDraftPreview' => true, 'isStudioPreview' => true,
        ])->toResponse($request);

        return response()->json(['html' => $frame->getContent()])->header('Cache-Control', 'private, no-store');
    }
}
