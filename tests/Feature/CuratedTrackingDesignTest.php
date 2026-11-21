<?php

use App\Actions\DesignDrafts\ChangeTrackingPublication;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Models\DesignDraftRevision;
use App\Support\Testing\FakePipelinePayloads;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCoreFixtures();
    $this->withoutVite();
    Http::preventStrayRequests();
    config(['services.pipeline.api_url' => 'https://pipeline.example/api']);
    $this->actingAs($this->staff = $this->makeCompanyAdmin());
    $this->company = $this->makeCompany(['pipeline_company_id' => 1001, 'enable_map' => true]);
    $this->settings = [
        'name' => 'Original proposal', 'layout' => 'journey.v1', 'palette' => 'company',
        'theme_id' => null, 'logo_image_id' => null, 'banner_image_id' => null, 'footer_image_id' => null,
        'headline_style' => 'serif', 'sections' => ['enable_map'], 'search_option' => 'bol', 'tracking_number' => 'BOL123',
    ];
});

it('starts a separate proposal from the published revision and preserves its source through a rollback', function () {
    $logo = $this->makeImage('logo');
    $theme = $this->makeTheme();
    $order = ['references', 'receiving', 'instructions', 'documents', 'map'];
    $settings = [...$this->settings, 'theme_id' => $theme->id, 'logo_image_id' => $logo->id, 'section_orders' => ['journey.v1' => $order]];
    $save = app(SaveDesignDraft::class);
    $source = $save->execute($this->company, $settings, $this->staff->id);
    $revision = $source->revisions()->sole();
    $proof = $revision->proofs()->create(['snapshot' => [], 'created_by' => $this->staff->id, 'shipment_reference' => 'BOL123']);
    $proof->forceFill(['status' => 'ready', 'approved_at' => now()])->save();
    $publish = app(ChangeTrackingPublication::class);
    $publish->execute($this->company, $proof, 0, $this->staff->id);
    $publication = $this->company->publications()->sole();

    $save->execute($this->company, [...$settings, 'base_version' => 1, 'layout' => 'record.v1'], $this->staff->id, $source);
    $theme->delete();
    $this->company->update(['enable_map' => false]);

    $create = route('admin.drafts.create', $this->company->uuid);
    $response = $this->get($create)->assertInertia(fn (Assert $page) => $page
        ->where('draft', null)->has('revisions', 0)->where('initialValues.name', '')
        ->where('initialValues.base_version', null)->where('initialValues.tracking_number', null)
        ->where('initialValues.layout', 'journey.v1')->where('initialValues.palette', 'company')
        ->where('initialValues.headline_style', 'serif')->where('initialValues.theme_id', $theme->id)
        ->where('initialValues.logo_image_id', $logo->id)->where('initialValues.sections', [])
        ->where('initialValues.source_publication_id', $publication->id));
    $form = $response->inertiaProps('initialValues');
    expect($form['section_orders']['journey.v1'])->toBe($order);

    $baseline = $publication->baselineRevision;
    $this->company->update(['theme_id' => $this->makeTheme()->id]);
    $publish->execute($this->company, null, $publication->id, $this->staff->id, rollback: true);
    $this->get($create)->assertInertia(fn (Assert $page) => $page->where('initialValues.layout', 'classic.v1')
        ->where('initialValues.theme_id', $baseline->design['theme']['id']));

    $form['name'] = 'Next season';
    $form['tracking_number'] = 'BOL123';
    $this->fakeTrackingApisFixture($this->company);
    $preview = $this->postJson(route('admin.drafts.studio-preview', $this->company->uuid), $form)->assertOk();
    $document = HTMLDocument::createFromString($preview->json('html'), LIBXML_NOERROR);
    $json = $document->querySelector('script[data-page]')?->textContent ?? $document->getElementById('app')->getAttribute('data-page');
    $frame = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    expect($frame['component'])->toBe('brandedTracking/Journey')
        ->and($frame['props']['initialCompany']['theme']['colors'])->toBe($revision->design['theme']['colors'])
        ->and($frame['props']['initialTrackingData']['bolNum'])->toBe('BOL123')
        ->and($frame['props']['initialShipmentCoordinates'])->toBeNull();
    expect($this->company->drafts()->count())->toBe(2);

    $this->post(route('admin.drafts.store', $this->company->uuid), $form)->assertSessionHasNoErrors();
    $copy = $this->company->drafts()->where('name', 'Next season')->sole()->revisions()->sole();
    expect($copy->version)->toBe(1)
        ->and($copy->design['sections'])->toBe([])
        ->and($copy->design['section_orders']['journey.v1'])->toBe($order)
        ->and($copy->design['theme'])->toBe($revision->design['theme'])
        ->and($copy->logo_image_id)->toBe($revision->logo_image_id)
        ->and($source->revisions()->count())->toBe(2)
        ->and($this->company->publications()->latest('id')->first()->effectiveRevision()->id)->toBe($baseline->id);
});

it('keeps original company branding and current entitlements for a first proposal', function () {
    $this->get(route('admin.drafts.create', $this->company->uuid))
        ->assertInertia(fn (Assert $page) => $page->where('initialValues.layout', 'classic.v1')
            ->where('initialValues.source_publication_id', null)->where('initialValues.theme_id', $this->company->theme_id)
            ->where('initialValues.sections', fn ($sections) => $sections->contains('enable_map'))
            ->has('catalog.layouts', 4)->has('catalog.palettes', 6)->has('catalog.modules', 5));
    expect($this->company->drafts()->count())->toBe(0);
});

it('rejects unsupported layout settings for both saving and preview before searching for shipments', function (string $field, mixed $value) {
    foreach (['admin.drafts.store', 'admin.drafts.studio-preview'] as $route) {
        $this->postJson(route($route, $this->company->uuid), [...$this->settings, $field => $value])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }
    Http::assertNothingSent();
    expect($this->company->drafts()->count())->toBe(0);
})->with([
    'unknown layout' => ['layout', 'experimental.v1'],
    'unregistered palette' => ['palette', 'random'],
    'arbitrary headline' => ['headline_style', 'script'],
    'fixed Classic order' => ['section_orders', ['classic.v1' => []]],
    'duplicate modules' => ['section_orders', ['journey.v1' => ['map', 'receiving', 'map', 'documents', 'references']]],
    'missing modules' => ['section_orders', ['record.v1' => ['receiving', 'references']]],
]);

it('scopes unsaved previews and publication seeds to authorized staff and the selected company', function () {
    $draft = app(SaveDesignDraft::class)->execute($this->company, $this->settings, $this->staff->id);
    $revision = $draft->revisions()->sole();
    $publication = $this->company->publications()->create(['baseline_revision_id' => $revision->id, 'action' => 'rollback', 'created_by' => $this->staff->id]);
    $other = $this->makeCompany(['pipeline_company_id' => 2002]);
    $preview = route('admin.drafts.studio-preview', $other->uuid);
    $this->app['auth']->guard()->logout();
    $this->postJson($preview, $this->settings)->assertUnauthorized();
    $this->actingAs($this->makeUserWithPermission('company:show'))->postJson($preview, $this->settings)->assertForbidden();
    $this->actingAs($this->staff);
    $this->postJson($preview, [...$this->settings, 'draft_uuid' => $draft->uuid, 'base_version' => 1])->assertNotFound();
    foreach (['admin.drafts.store', 'admin.drafts.studio-preview'] as $route) {
        $this->postJson(route($route, $other->uuid), [...$this->settings, 'source_publication_id' => $publication->id])->assertNotFound();
    }
    Http::assertNothingSent();
    Http::fake(['*/shipmentSearch' => Http::response(FakePipelinePayloads::shipmentSearch('BOL123', 1001))]);
    $this->postJson($preview, $this->settings)->assertUnprocessable()->assertJsonValidationErrors('tracking_number');
    Http::assertSentCount(1);
    expect($other->drafts()->count())->toBe(0)->and(DesignDraftRevision::query()->count())->toBe(1);
});
