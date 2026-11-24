<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;
use App\Models\DesignDraftRevision;
use Illuminate\Contracts\Pagination\Paginator;

final class ListApprovedDesignRevisions
{
    public function execute(Company $company, array $query): Paginator
    {
        return DesignDraftRevision::query()->select(['id', 'design_draft_id', 'version', 'approved_at'])
            ->whereHas('draft', fn ($drafts) => $drafts->where('company_id', $company->id))
            ->whereNotNull('approved_at')->with('draft:id,uuid,name')
            ->latest('approved_at')->latest('id')
            ->simplePaginate(5, pageName: 'approvals_page')->appends($query)
            ->through(fn (DesignDraftRevision $revision) => [
                'draftUuid' => $revision->draft->uuid, 'name' => $revision->draft->name,
                'version' => $revision->version, 'approvedAt' => $revision->approved_at,
            ]);
    }
}
