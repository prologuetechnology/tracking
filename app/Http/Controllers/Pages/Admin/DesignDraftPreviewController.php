<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\StudioShipmentSnapshot;
use App\Actions\DesignDrafts\TrackingDesignPage;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\DesignDraftRevision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DesignDraftPreviewController extends Controller
{
    public function show(Company $company, DesignDraft $draft, DesignDraftRevision $revision): Response
    {
        $live = $company->publications()->latest('id')->first();

        return Inertia::render('admin/designDrafts/Preview', [
            'company' => $company->only(['uuid', 'name']),
            'draft' => $draft->only(['uuid', 'name']),
            'proofs' => $revision->proofs()->latest('id')->get(['uuid', 'shipment_reference', 'status', 'created_at', 'updated_at'])->map(fn ($proof) => [
                ...$proof->toArray(), 'can_retry' => $proof->status === 'failed' || ($proof->status === 'pending' && $proof->updated_at->lt(now()->subMinutes(10))),
            ]),
            'publication' => (int) $live?->id,
            'isLive' => $live?->published_revision_id === $revision->id,
            'revision' => $revision->only(['version', 'search_option', 'tracking_number', 'approved_at']),
        ]);
    }

    public function render(Company $company, DesignDraft $draft, DesignDraftRevision $revision, Request $request, TrackingDesignPage $pages, StudioShipmentSnapshot $snapshots): Response|RedirectResponse
    {
        $lookup = validator($request->query(), [
            'trackingNumber' => ['required', 'string', 'max:128'],
            'searchOption' => ['required', Rule::in(['bol', 'carrierPro'])],
        ]);
        if ($lookup->fails()) {
            return Inertia::render('admin/designDrafts/Unavailable');
        }
        $input = $lookup->validated();
        if ($request->boolean('capture') || $request->has('snapshot_token')) {
            try {
                $token = $request->validate(['snapshot_token' => ['sometimes', 'required', 'uuid']])['snapshot_token'] ?? null;
                $capture = $snapshots->savedRevision($company, $revision, $request->user()->id, $input, $token);
            } catch (ValidationException $exception) {
                return Inertia::render('admin/designDrafts/Unavailable', ['previewError' => collect($exception->errors())->flatten()->first()]);
            }
            if ($token === null) {
                return to_route('admin.drafts.render', [$company->uuid, $draft->uuid, $revision->version, ...$input, 'snapshot_token' => $capture['token']]);
            }

            return Inertia::render($capture['page']['component'], [
                ...$capture['page']['props'], 'isDraftPreview' => true, 'isStudioPreview' => true,
                'proofCapture' => ['token' => $token, 'at' => $capture['captured_at'], 'expiresAt' => $capture['expires_at']],
            ]);
        }
        $page = $pages->preview($company, $revision, $input);

        return $page
            ? Inertia::render($page['component'], [...$page['props'], 'isDraftPreview' => true])
            : Inertia::render('admin/designDrafts/Unavailable');
    }
}
