<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;
use App\Models\DesignProof;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeTrackingPublication
{
    public function __construct(private readonly SaveDesignDraft $saveDraft) {}

    public function execute(Company $company, ?DesignProof $proof, int $expected, int $staff, bool $rollback = false): void
    {
        DB::transaction(function () use ($company, $proof, $expected, $staff, $rollback) {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $current = $company->publications()->latest('id')->first();
            if ((int) $current?->id !== $expected) {
                throw ValidationException::withMessages(['publication' => 'The live design changed. Reload before continuing.']);
            }
            if ($proof) {
                abort_unless($proof->approved_at && $proof->status === 'ready' && $proof->revision->draft->company_id === $company->id, 409);
            }
            if ($proof?->id === $current?->design_proof_id) {
                return;
            }
            $company->publications()->create([
                'baseline_revision_id' => $current?->baseline_revision_id ?? $this->captureBaseline($company, $staff),
                'design_proof_id' => $proof?->id,
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
