<?php

namespace App\Actions\DesignDrafts;

use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\DesignDraftRevision;
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
            $this->appendRevision($draft, ($previous?->version ?? 0) + 1, $design, $input['search_option'], $input['tracking_number'] ?? null, $author);

            return $draft;
        }, attempts: 3);
    }

    public function fork(Company $company, DesignDraftRevision $source, string $name, int $author): DesignDraft
    {
        abort_unless($source->draft->company_id === $company->id, 404);

        return DB::transaction(function () use ($company, $source, $name, $author) {
            $draft = $company->drafts()->create(['name' => $name]);
            $design = $source->design;
            $design['sections'] = array_values(array_intersect($design['sections'], ClassicDesign::availableSections($company)));
            $this->appendRevision($draft, 1, $design, $source->search_option, $source->tracking_number, $author);

            return $draft;
        }, attempts: 3);
    }

    private function appendRevision(DesignDraft $draft, int $version, array $design, string $searchOption, ?string $trackingNumber, int $author): void
    {
        $draft->revisions()->create([
            'version' => $version,
            'created_by' => $author,
            'design' => $design,
            'theme_version_id' => $design['theme']['theme_version_id'] ?? null,
            'logo_image_id' => $design['logo']['id'] ?? null,
            'banner_image_id' => $design['banner']['id'] ?? null,
            'footer_image_id' => $design['footer']['id'] ?? null,
            'tracking_number' => $trackingNumber,
            'search_option' => $searchOption,
        ]);
    }
}
