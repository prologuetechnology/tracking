<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\ClassicDesign;
use App\Actions\DesignDrafts\DesignCatalog;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDesignDraftRequest;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\Image;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DesignDraftController extends Controller
{
    public function index(Company $company): Response
    {
        return Inertia::render('admin/designDrafts/Index', [
            'company' => $company->only(['uuid', 'name']),
            'publications' => $company->publications()->latest('id')->with(['proof:id,uuid,design_draft_revision_id', 'proof.revision:id,design_draft_id,version', 'proof.revision.draft:id,uuid,name'])
                ->get()->map(fn ($entry) => [
                    'id' => $entry->id, 'action' => $entry->action, 'at' => $entry->created_at,
                    'name' => $entry->proof?->revision->draft->name,
                    'draft_uuid' => $entry->proof?->revision->draft->uuid,
                    'proof_uuid' => $entry->proof?->uuid,
                    'version' => $entry->proof?->revision->version,
                ]),
            'drafts' => $company->drafts()->withCount('revisions')->latest('updated_at')->orderByDesc('id')
                ->paginate(20, ['id', 'uuid', 'name', 'updated_at'])->through(fn ($draft) => $draft->only(['uuid', 'name', 'revisions_count', 'updated_at'])),
        ]);
    }

    public function create(Company $company): Response
    {
        return $this->editor($company);
    }

    public function show(Company $company, DesignDraft $draft): Response
    {
        return $this->editor($company, $draft);
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

    private function editor(Company $company, ?DesignDraft $draft = null): Response
    {
        $publication = $draft === null ? $company->publications()->latest('id')->first() : null;
        $revision = $draft ? $draft->revisions()->latest('version')->firstOrFail() : $publication?->effectiveRevision();
        $available = ClassicDesign::availableSections($company);
        $images = Image::query()->with('imageType:id,name')->whereHas('imageType', fn ($q) => $q->whereIn('name', ClassicDesign::ASSETS))
            ->orderBy('name')->get(['id', 'name', 'image_type_id'])->groupBy('imageType.name')
            ->map(fn ($group) => $group->map->only(['id', 'name'])->values());
        $form = [
            'palette' => $revision?->design['palette'] ?? 'company',
            'headline_style' => $revision?->design['headline_style'] ?? 'layout',
            'section_orders' => array_replace(DesignCatalog::orders(), $revision?->design['section_orders'] ?? []),
            'layout' => $revision?->design['layout'] ?? ClassicDesign::LAYOUT,
            'emphasis' => $revision?->design['emphasis'] ?? 'delivery',
            'name' => $draft?->name ?? '',
            'theme_id' => $revision ? ($revision->design['theme']['id'] ?? null) : $company->theme_id,
            'sections' => $revision ? array_values(array_intersect($revision->design['sections'], $available)) : $available,
            'search_option' => $draft ? $revision->search_option : 'bol',
            'tracking_number' => $draft ? $revision->tracking_number : null,
            'base_version' => $draft ? $revision->version : null,
            'source_publication_id' => $publication?->id,
        ];
        foreach (ClassicDesign::ASSETS as $type) {
            $field = $type.'_image_id';
            $form[$field] = $revision ? $revision->$field : $company->$field;
        }
        $themes = Theme::query()->orderBy('name')->get(['id', 'name'])->toBase();
        $savedTheme = $revision?->design['theme'] ?? null;
        if ($savedTheme && ! $themes->contains('id', $savedTheme['id'])) {
            $themes->push(['id' => $savedTheme['id'], 'name' => $savedTheme['name'].' (saved)']);
        }

        return Inertia::render('admin/designDrafts/Edit', [
            'catalog' => DesignCatalog::editor(),
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
