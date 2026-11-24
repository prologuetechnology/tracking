<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;
use App\Models\DesignProof;

final class CompanyDesignWorkspace
{
    public function execute(Company $company, bool $canDesign, array $lookup): array
    {
        $publication = $company->publications()->latest('id')->first();
        $live = LiveDesignSummary::forPublication($publication);
        $overview = [
            'layout' => DesignCatalog::LAYOUTS[$live['layout']]['name'] ?? 'Classic',
        ];
        if (! $canDesign) {
            return $overview;
        }

        $approved = DesignProof::query()
            ->select(['id', 'uuid', 'design_draft_revision_id', 'shipment_reference', 'approved_at'])
            ->whereHas('revision.draft', fn ($drafts) => $drafts->where('company_id', $company->id))
            ->where('status', 'ready')->whereNotNull('approved_at')
            ->with(['revision:id,design_draft_id,version', 'revision.draft:id,uuid,name'])
            ->latest('approved_at')->latest('id')
            ->simplePaginate(5, pageName: 'proofs_page')->appends($lookup)
            ->through(fn (DesignProof $proof) => [
                'uuid' => $proof->uuid,
                'draftUuid' => $proof->revision->draft->uuid,
                'name' => $proof->revision->draft->name,
                'version' => $proof->revision->version,
                'reference' => $proof->shipment_reference,
                'approvedAt' => $proof->approved_at,
                'live' => $publication?->design_proof_id === $proof->id,
            ]);

        $liveProof = $publication?->design_proof_id
            ? $publication->proof()->select(['id', 'uuid', 'design_draft_revision_id'])->with(['revision:id,design_draft_id,version', 'revision.draft:id,uuid'])->firstOrFail()
            : null;

        return [
            ...$overview,
            'liveProof' => $liveProof ? [
                'uuid' => $liveProof->uuid, 'draftUuid' => $liveProof->revision->draft->uuid, 'version' => $liveProof->revision->version,
            ] : null,
            'live' => $live['label'],
            'approvedProofs' => $approved,
            'recentDrafts' => $company->drafts()->select(['id', 'uuid', 'name'])->withCount('revisions')
                ->latest('updated_at')->latest('id')->limit(5)->get()
                ->map(fn ($draft) => $draft->only(['uuid', 'name', 'revisions_count'])),
        ];
    }
}
