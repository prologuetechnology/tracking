<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\TrackingDesignPage;
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
        $live = $company->publications()->latest('id')->with('proof:id,uuid')->first();

        return Inertia::render('admin/designDrafts/Preview', [
            'company' => $company->only(['uuid', 'name']),
            'draft' => $draft->only(['uuid', 'name']),
            'proofs' => $revision->proofs()->latest('id')->get(['uuid', 'shipment_reference', 'status', 'created_at', 'customer_approver', 'approved_at', 'updated_at'])->map(fn ($proof) => [
                ...$proof->toArray(), 'can_retry' => $proof->status === 'failed' || ($proof->status === 'pending' && $proof->updated_at->lt(now()->subMinutes(10))),
            ]),
            'publication' => (int) $live?->id,
            'activeProof' => $live?->proof?->uuid,
            'revision' => $revision->only(['version', 'search_option', 'tracking_number']),
        ]);
    }

    public function render(Company $company, DesignDraft $draft, DesignDraftRevision $revision, Request $request, TrackingDesignPage $pages): Response
    {
        $lookup = validator($request->query(), [
            'trackingNumber' => ['required', 'string', 'max:128'],
            'searchOption' => ['required', Rule::in(['bol', 'carrierPro'])],
        ]);
        $page = $lookup->passes() ? $pages->preview($company, $revision, $lookup->validated()) : null;

        return $page
            ? Inertia::render($page['component'], [...$page['props'], 'isDraftPreview' => true])
            : Inertia::render('admin/designDrafts/Unavailable');
    }
}
