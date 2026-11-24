<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;
use App\Models\DesignDraftRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeTrackingPublication
{
    public function __construct(private readonly SaveDesignDraft $saveDraft) {}

    public function execute(Company $company, ?DesignDraftRevision $revision, int $expected, int $staff, bool $rollback = false): void
    {
        DB::transaction(function () use ($company, $revision, $expected, $staff, $rollback) {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $current = $company->publications()->latest('id')->first();
            if ((int) $current?->id !== $expected) {
                throw ValidationException::withMessages(['publication' => 'The live design changed. Reload before continuing.']);
            }
            if ($revision) {
                abort_unless($revision->fresh()?->approved_at && $revision->draft->company_id === $company->id, 409);
            }
            if ($revision?->id === $current?->published_revision_id) {
                return;
            }
            $company->publications()->create([
                'baseline_revision_id' => $current?->baseline_revision_id ?? $this->captureBaseline($company, $staff),
                'published_revision_id' => $revision?->id,
                'action' => $rollback ? 'rollback' : 'publish',
                'created_by' => $staff,
            ]);
        }, attempts: 3);
    }

    private function captureBaseline(Company $company, int $staff): int
    {
        $draft = $this->saveDraft->execute($company, [
            'name' => 'Classic before first publication',
            'theme_id' => $company->theme_id,
            'logo_image_id' => $company->logo_image_id,
            'banner_image_id' => $company->banner_image_id,
            'footer_image_id' => $company->footer_image_id,
            'sections' => ClassicDesign::availableSections($company),
            'search_option' => 'bol',
        ], $staff);

        return $draft->revisions()->sole()->id;
    }
}
