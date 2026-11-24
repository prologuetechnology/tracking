<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\ClassicDesign;
use App\Actions\DesignDrafts\DesignBrandImages;
use App\Actions\DesignDrafts\DesignCatalog;
use App\Actions\DesignDrafts\DraftShipmentLookup;
use App\Actions\DesignDrafts\LiveDesignSummary;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDesignDraftRequest;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\DesignDraftRevision;
use App\Models\Image;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignDraftController extends Controller
{
    public function index(Company $company, Request $request): Response
    {
        $lookup = DraftShipmentLookup::fromQuery($request);

        return Inertia::render('admin/designDrafts/Index', [
            'shipmentLookup' => $lookup ?: null,
            'company' => $company->only(['uuid', 'name']),
            'publications' => $company->publications()->latest('id')->with(['proof:id,uuid,design_draft_revision_id', 'proof.revision:id,design_draft_id,version', 'proof.revision.draft:id,uuid,name'])
                ->get()->map(fn ($entry) => [
                    'id' => $entry->id, 'action' => $entry->action, 'at' => $entry->created_at,
                    'name' => $entry->proof?->revision->draft->name,
                    'draft_uuid' => $entry->proof?->revision->draft->uuid,
                    'proof_uuid' => $entry->proof?->uuid,
                    'version' => $entry->proof?->revision->version,
                ]),
            'drafts' => $company->drafts()->select(['id', 'uuid', 'name', 'updated_at'])->withCount('revisions')
                ->addSelect(['layout' => DesignDraftRevision::select('design->layout')->whereColumn('design_draft_id', 'design_drafts.id')->latest('version')->limit(1)])
                ->latest('updated_at')->orderByDesc('id')->paginate(20)->appends($lookup)->through(fn ($draft) => [
                    ...$draft->only(['uuid', 'name', 'revisions_count', 'updated_at']),
                    'layout' => DesignCatalog::LAYOUTS[$draft->layout]['name'] ?? null,
                ]),
        ]);
    }

    public function create(Company $company, Request $request): Response
    {
        return $this->editor($company, lookup: DraftShipmentLookup::fromQuery($request));
    }

    public function show(Company $company, DesignDraft $draft): Response
    {
        return $this->editor($company, $draft);
    }

    public function fork(Company $company, DesignDraft $draft, DesignDraftRevision $revision, Request $request, SaveDesignDraft $save): RedirectResponse
    {
        $input = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $copy = $save->fork($company, $revision, $input['name'], $request->user()->id);

        return to_route('admin.drafts.show', [$company->uuid, $copy->uuid]);
    }

    public function store(Company $company, SaveDesignDraftRequest $request, SaveDesignDraft $save): RedirectResponse
    {
        $draft = $save->execute($company, $request->validated(), $request->user()->id);

        return to_route('admin.drafts.show', [$company->uuid, $draft->uuid]);
    }

    public function update(Company $company, DesignDraft $draft, SaveDesignDraftRequest $request, SaveDesignDraft $save): RedirectResponse
    {
        $save->execute($company, $request->validated(), $request->user()->id, $draft);

        return to_route('admin.drafts.show', [$company->uuid, $draft->uuid]);
    }

    private function editor(Company $company, ?DesignDraft $draft = null, array $lookup = []): Response
    {
        $publication = $company->publications()->latest('id')->first();
        $revision = $draft ? $draft->revisions()->latest('version')->firstOrFail() : $publication?->effectiveRevision();
        $available = ClassicDesign::availableSections($company);
        $images = Image::query()->with('imageType:id,name')->whereHas('imageType', fn ($q) => $q->whereIn('name', ClassicDesign::ASSETS))
            ->orderBy('name')->get(['id', 'name', 'file_path', 'image_type_id'])->groupBy('imageType.name')
            ->map(fn ($group) => $group->map(fn (Image $image) => [
                ...$image->only(['id', 'name']), 'preview_url' => DesignBrandImages::urlForPath($image->file_path),
            ])->values());
        $form = [
            ...ClassicDesign::editorSettings($company, $revision?->design),
            'upgrade_presentation' => false,
            'name' => $draft?->name ?? '',
            'search_option' => $draft ? $revision->search_option : ($lookup['searchOption'] ?? 'bol'),
            'tracking_number' => $draft ? $revision->tracking_number : ($lookup['trackingNumber'] ?? null),
            'base_version' => $draft ? $revision->version : null,
            'source_publication_id' => $draft ? null : $publication?->id,
        ];
        $themes = Theme::query()->with('versions:id,theme_id,version,name,style')->orderBy('name')->get(['id', 'name'])->toBase();
        $savedTheme = $revision?->design['theme'] ?? null;
        if ($savedTheme && ! $themes->contains('id', $savedTheme['id'])) {
            $themes->push(['id' => $savedTheme['id'], 'name' => $savedTheme['name'].' (saved)', 'versions' => []]);
        }

        return Inertia::render('admin/designDrafts/Edit', [
            'catalog' => DesignCatalog::editor(),
            'classicStyle' => $company->baselineTheme()?->snapshot(),
            'liveDesign' => LiveDesignSummary::forPublication($publication, $company),
            'canUpgradePresentation' => $revision !== null && ($revision->design['presentation_version'] ?? 1) < 2 && $revision->design['layout'] !== ClassicDesign::LAYOUT,
            'company' => $company->only(['uuid', 'name']),
            'draft' => $draft?->only(['uuid', 'name']),
            'initialValues' => $form,
            'images' => $images,
            'themes' => $themes,
            'sections' => array_intersect_key(ClassicDesign::SECTIONS, array_flip($available)),
            'revisions' => $draft?->revisions()->orderByDesc('version')->get(['version', 'created_at']) ?? [],
        ]);
    }
}
