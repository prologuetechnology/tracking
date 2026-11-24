<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\ChangeTrackingPublication;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\DesignDraftRevision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrackingPublicationController extends Controller
{
    public function approve(Company $company, DesignDraft $draft, DesignDraftRevision $revision, Request $request): RedirectResponse
    {
        $revision->approve($request->user()->id);

        return back();
    }

    public function publish(Company $company, DesignDraft $draft, DesignDraftRevision $revision, Request $request, ChangeTrackingPublication $change): RedirectResponse
    {
        $input = $request->validate(['publication' => ['required', 'integer', 'min:0']]);
        $change->execute($company, $revision, (int) $input['publication'], $request->user()->id);

        return back();
    }

    public function rollback(Company $company, Request $request, ChangeTrackingPublication $change): RedirectResponse
    {
        $input = $request->validate([
            'publication' => ['required', 'integer', 'min:1'],
            'restore' => ['required', 'integer', 'min:0', 'lt:publication'],
        ]);
        $revision = $input['restore'] ? $company->publications()->findOrFail($input['restore'])->publishedRevision : null;
        $change->execute($company, $revision, (int) $input['publication'], $request->user()->id, rollback: true);

        return to_route('admin.drafts.index', $company->uuid);
    }
}
