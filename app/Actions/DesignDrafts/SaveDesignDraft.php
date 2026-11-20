<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;
use App\Models\DesignDraft;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveDesignDraft
{
    public function __construct(private readonly ClassicDesign $classic) {}

    public function execute(Company $company, array $input, int $author, ?DesignDraft $draft = null): DesignDraft
    {
        return DB::transaction(function () use ($company, $input, $author, $draft) {
            $previous = null;
            if ($draft) {
                $draft = $company->drafts()->lockForUpdate()->findOrFail($draft->id);
                $previous = $draft->revisions()->orderByDesc('version')->firstOrFail();
                if ($previous->version !== (int) $input['base_version']) {
                    throw ValidationException::withMessages(['base_version' => 'Another revision was saved. Reopen the draft before saving your changes.']);
                }
                $draft->name = $input['name'];
                $draft->touch();
            } else {
                $draft = $company->drafts()->create(['name' => $input['name']]);
            }
            $design = $this->classic->snapshot($company, $input, $previous);
            $draft->revisions()->create([
                'version' => ($previous?->version ?? 0) + 1,
                'created_by' => $author,
                'design' => $design,
                'logo_image_id' => $design['logo']['id'] ?? null,
                'banner_image_id' => $design['banner']['id'] ?? null,
                'footer_image_id' => $design['footer']['id'] ?? null,
                'tracking_number' => $input['tracking_number'] ?? null,
                'search_option' => $input['search_option'],
            ]);

            return $draft;
        }, attempts: 3);
    }
}
