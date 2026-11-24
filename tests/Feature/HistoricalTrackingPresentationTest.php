<?php

use App\Actions\DesignDrafts\ChangeTrackingPublication;
use App\Actions\DesignDrafts\SaveDesignDraft;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCoreFixtures();
    $this->withoutVite();
    config(['services.pipeline.api_url' => 'https://pipeline.example/api', 'services.pipeline.base_url' => 'https://pipeline.example']);
    Http::preventStrayRequests();
    $this->actingAs($this->staff = $this->makeCompanyAdmin());
    $this->company = $this->makeCompany(['pipeline_company_id' => 1001, 'enable_map' => true]);
    $this->fakeTrackingApisFixture($this->company);
    $this->save = app(SaveDesignDraft::class);
    $this->settings = [
        'name' => 'Earlier edition', 'layout' => 'answer.v1', 'theme_id' => null, 'logo_image_id' => null,
        'banner_image_id' => null, 'footer_image_id' => null, 'sections' => ['enable_map'], 'search_option' => 'bol',
        'tracking_number' => 'BOL123',
    ];
    $this->draft = $this->save->execute($this->company, $this->settings, $this->staff->id);
    $this->old = $this->draft->revisions()->sole();
    $this->preview = route('admin.drafts.studio-preview', $this->company->uuid);
});

function historicalFrameProps(string $html): array
{
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $page = $document->querySelector('script[data-page]')?->textContent ?? $document->getElementById('app')->getAttribute('data-page');

    return json_decode($page, true, flags: JSON_THROW_ON_ERROR)['props'];
}

it('retains the original presentation on reopen, fresh proposals and rename-only revisions of a fork', function (string $layout, bool $ordered) {
    $design = $this->old->design;
    $design['layout'] = $layout;
    unset($design['custom_colors'], $design['hidden_modules'], $design['presentation_version']);
    if (! $ordered) {
        unset($design['section_orders'], $design['headline_style'], $design['palette']);
    }
    $this->old->update(['design' => $design]);
    $this->old->approve($this->staff->id);
    app(ChangeTrackingPublication::class)->execute($this->company, $this->old, 0, $this->staff->id);
    $this->company->update(['enable_map' => false]);

    $live = $this->postJson($this->preview, ['preview_mode' => 'live', 'search_option' => 'bol', 'tracking_number' => 'BOL123'])->assertOk();
    $props = historicalFrameProps($live->json('html'));
    expect($props['presentationVersion'])->toBe(1)->and($props['sectionOrder'])->toBe($ordered ? $design['section_orders'][$layout] : null);

    $editor = route('admin.drafts.show', [$this->company->uuid, $this->draft->uuid]);
    $saved = $this->get($editor);
    expect($saved->inertiaProps('canUpgradePresentation'))->toBeTrue();
    $existing = [...$saved->inertiaProps('initialValues'), 'draft_uuid' => $this->draft->uuid];
    $fresh = $this->get(route('admin.drafts.create', $this->company->uuid))->inertiaProps('initialValues');
    foreach ([$existing, $fresh] as $form) {
        $draft = $this->postJson($this->preview, [...$form, 'tracking_number' => 'BOL123', 'snapshot_token' => $live->json('snapshot_token')])->assertOk();
        $draftProps = historicalFrameProps($draft->json('html'));
        expect($draftProps['presentationVersion'])->toBe($props['presentationVersion'])
            ->and($draftProps['sectionOrder'])->toBe($props['sectionOrder'])
            ->and($draftProps['initialCompany'])->toBe($props['initialCompany']);
    }

    $this->post(route('admin.drafts.store', $this->company->uuid), [...$fresh, 'name' => 'Another design'])->assertSessionHasNoErrors();
    $filtered = [...$design, 'sections' => []];
    $proposal = $this->company->drafts()->where('name', 'Another design')->sole();
    expect($proposal->revisions()->sole()->design)->toBe($filtered);

    $fork = $this->save->fork($this->company, $this->old, 'Private copy', $this->staff->id);
    $forkEditor = route('admin.drafts.show', [$this->company->uuid, $fork->uuid]);
    $form = $this->get($forkEditor)->inertiaProps('initialValues');
    $this->put($forkEditor, [...$form, 'name' => 'Named for approval', 'tracking_number' => 'BOL456'])->assertSessionHasNoErrors();
    expect($fork->revisions()->where('version', 2)->sole()->design)->toBe($filtered)
        ->and($this->old->fresh()->design)->toBe($design)
        ->and($this->company->publications()->count())->toBe(1);
})->with([
    'original Answer without section ordering' => ['answer.v1', false],
    'earlier ordered Answer' => ['answer.v1', true],
    'earlier Journey' => ['journey.v1', true],
    'earlier Record' => ['record.v1', true],
]);

it('upgrades the new presentation in preview and the next saved revision only after a visual edit or opt-in', function (array $change) {
    $design = $this->old->design;
    unset($design['presentation_version'], $design['section_orders'], $design['hidden_modules'], $design['custom_colors']);
    $this->old->update(['design' => $design]);
    $editor = route('admin.drafts.show', [$this->company->uuid, $this->draft->uuid]);
    $form = [...$this->get($editor)->inertiaProps('initialValues'), ...$change];
    $preview = $this->postJson($this->preview, [...$form, 'draft_uuid' => $this->draft->uuid])->assertOk();
    expect(historicalFrameProps($preview->json('html'))['presentationVersion'])->toBe(2);
    $this->put($editor, $form)->assertSessionHasNoErrors();
    $new = $this->draft->revisions()->where('version', 2)->sole();
    expect($new->design['presentation_version'])->toBe(2)
        ->and($new->design['section_orders']['answer.v1'])->not->toBeEmpty()
        ->and($this->old->fresh()->design)->toBe($design)
        ->and($this->get($editor)->inertiaProps('canUpgradePresentation'))->toBeFalse();
})->with([
    'explicit upgrade with identical settings' => [['upgrade_presentation' => true]],
    'choosing a new palette' => [['palette' => 'pine']],
]);
