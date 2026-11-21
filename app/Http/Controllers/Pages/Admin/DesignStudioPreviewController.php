<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\ClassicDesign;
use App\Actions\DesignDrafts\LiveDesignSummary;
use App\Actions\DesignDrafts\StudioShipmentSnapshot;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewDesignDraftRequest;
use App\Models\Company;
use App\Models\DesignDraftRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DesignStudioPreviewController extends Controller
{
    public function __invoke(Company $company, PreviewDesignDraftRequest $request, ClassicDesign $designs, StudioShipmentSnapshot $snapshots): JsonResponse
    {
        $input = $request->validated();
        $publication = $company->publications()->latest('id')->first();

        if (($input['preview_mode'] ?? 'draft') === 'live') {
            $revision = $publication?->effectiveRevision();
            if (! $revision) {
                $design = $designs->snapshot($company, [
                    ...$company->only(['theme_id', 'logo_image_id', 'banner_image_id', 'footer_image_id']),
                    'sections' => ClassicDesign::availableSections($company),
                ], null);
                $revision = new DesignDraftRevision(['design' => $design]);
            }
        } else {
            $previous = null;
            if ($input['draft_uuid'] ?? null) {
                $draft = $company->drafts()->where('uuid', $input['draft_uuid'])->firstOrFail();
                $previous = $draft->revisions()->where('version', $input['base_version'] ?? 0)->firstOrFail();
            }
            $revision = new DesignDraftRevision(['design' => $designs->snapshot($company, $input, $previous)]);
        }

        $snapshot = $snapshots->load($company, $request->user()->id, $input, $input['snapshot_token'] ?? null);
        $page = $snapshots->page($company, $revision, $snapshot);
        $renderId = (string) Str::uuid();
        $frame = Inertia::render($page['component'], [
            ...$page['props'], 'isDraftPreview' => true, 'isStudioPreview' => true, 'studioRenderId' => $renderId,
        ])->toResponse($request);

        return response()->json([
            'html' => $frame->getContent(),
            'snapshot_token' => $snapshot['token'],
            'captured_at' => $snapshot['captured_at'],
            'render_id' => $renderId,
            'live_design' => LiveDesignSummary::forPublication($publication),
        ])->header('Cache-Control', 'private, no-store');
    }
}
