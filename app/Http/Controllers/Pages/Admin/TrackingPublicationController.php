<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\ChangeTrackingPublication;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\DesignDraftRevision;
use App\Models\DesignProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrackingPublicationController extends Controller
{
    public function publish(Company $company, DesignDraft $draft, DesignDraftRevision $revision, DesignProof $proof, Request $request, ChangeTrackingPublication $change): RedirectResponse
    {
        $input = $request->validate(['publication' => ['required', 'integer', 'min:0']]);
        $change->execute($company, $proof, (int) $input['publication'], $request->user()->id);

        return to_route('admin.drafts.preview', [$company->uuid, $draft->uuid, $revision->version]);
    }

    public function rollback(Company $company, Request $request, ChangeTrackingPublication $change): RedirectResponse
    {
        $input = $request->validate([
            'publication' => ['required', 'integer', 'min:1'],
            'restore' => ['required', 'integer', 'min:0', 'lt:publication'],
        ]);
        $proof = $input['restore'] ? $company->publications()->findOrFail($input['restore'])->proof : null;
        $change->execute($company, $proof, (int) $input['publication'], $request->user()->id, rollback: true);

        return to_route('admin.drafts.index', $company->uuid);
    }
}
