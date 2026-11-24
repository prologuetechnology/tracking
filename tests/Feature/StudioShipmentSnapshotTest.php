<?php

use App\Actions\DesignDrafts\ChangeTrackingPublication;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Models\CompanyFeature;
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
    $this->company = $this->makeCompany(['pipeline_company_id' => 1001, 'enable_map' => true, 'enable_documents' => true]);
    $this->makeCompanyApiToken($this->company);
    $this->fakeTrackingApisFixture($this->company);
    $this->url = route('admin.drafts.studio-preview', $this->company->uuid);
    $this->draft = [
        ...$this->get(route('admin.drafts.create', $this->company->uuid))->inertiaProps('initialValues'),
        'layout' => 'journey.v1', 'name' => 'Receiving review', 'tracking_number' => 'BOL123',
    ];
});

function studioSnapshotFrame(string $html): array
{
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $json = $document->querySelector('script[data-page]')?->textContent ?? $document->getElementById('app')->getAttribute('data-page');

    return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
}

it('compares draft and current live design on a single shipment even after staff publish a different revision', function () {
    $first = $this->postJson($this->url, $this->draft)->assertOk()->assertJsonPath('live_design.label', 'Original Classic');
    $token = $first->json('snapshot_token');
    $calls = Http::recorded()->count();
    $frame = studioSnapshotFrame($first->json('html'));
    expect($frame['component'])->toBe('brandedTracking/Journey')->and($frame['props']['initialTrackingData']['bolNum'])->toBe('BOL123');
    $this->assertDatabaseCount('design_draft_revisions', 0);
    $compare = ['preview_mode' => 'live', 'snapshot_token' => $token, 'tracking_number' => 'BOL123', 'search_option' => 'bol'];
    $live = $this->postJson($this->url, $compare)->assertOk();
    expect(studioSnapshotFrame($live->json('html'))['component'])->toBe('brandedTracking/Classic');

    $saved = app(SaveDesignDraft::class)->execute($this->company, [...$this->draft, 'layout' => 'record.v1'], $this->staff->id);
    $revision = $saved->revisions()->sole();
    $revision->approve($this->staff->id);
    app(ChangeTrackingPublication::class)->execute($this->company, $revision, 0, $this->staff->id);
    $newLive = $this->postJson($this->url, $compare)->assertOk()->assertJsonPath('live_design.layout', 'record.v1');
    expect(studioSnapshotFrame($newLive->json('html'))['component'])->toBe('brandedTracking/Record');

    $changed = $this->postJson($this->url, [...$this->draft, 'snapshot_token' => $token, 'layout' => 'answer.v1', 'palette' => 'pine'])->assertOk();
    expect(studioSnapshotFrame($changed->json('html'))['component'])->toBe('brandedTracking/Answer')
        ->and($changed->json('captured_at'))->toBe($first->json('captured_at'))
        ->and($changed->json('snapshot_token'))->toBe($token)
        ->and(Http::recorded()->count())->toBe($calls);
    $refresh = $this->postJson($this->url, $this->draft)->assertOk();
    expect($refresh->json('snapshot_token'))->not->toBe($token)->and(Http::recorded()->count())->toBeGreaterThan($calls);
});

it('withholds hidden and revoked data on both draft and live rerenders', function () {
    $company = $this->company;
    foreach (['special_instructions', 'customer_pos'] as $slug) {
        CompanyFeature::firstOrCreate(['slug' => $slug], ['name' => $slug]);
    }
    $company->enableFeature(['special_instructions', 'customer_pos']);
    $all = ['enable_map', 'enable_documents', 'special_instructions', 'customer_pos'];
    $first = $this->postJson($this->url, [...$this->draft, 'sections' => $all])->assertOk();
    $loaded = studioSnapshotFrame($first->json('html'))['props'];
    expect($loaded['initialShipmentCoordinates'])->not->toBeEmpty()->and($loaded['initialShipmentDocuments'])->not->toBeEmpty()
        ->and($loaded['initialTrackingData'])->toHaveKeys(['poNumbers', 'specialInstructions']);
    $input = [...$this->draft, 'snapshot_token' => $first->json('snapshot_token')];
    $calls = Http::recorded()->count();
    $assertRedacted = function (array $input) {
        $response = $this->postJson($this->url, $input)->assertOk();
        $props = studioSnapshotFrame($response->json('html'))['props'];
        expect($props['initialShipmentCoordinates'])->toBeNull()->and($props['initialShipmentDocuments'])->toBe([])
            ->and($props['initialTrackingData'])->not->toHaveKey('poNumbers')->not->toHaveKey('specialInstructions');
    };
    $assertRedacted([...$input, 'sections' => []]);
    $company->update(['enable_map' => false]);
    $company->disableFeature(['special_instructions', 'customer_pos']);
    $company->apiToken()->delete();
    $assertRedacted([...$input, 'sections' => $all]);
    $assertRedacted([...$input, 'preview_mode' => 'live']);
    expect(Http::recorded()->count())->toBe($calls);
});

it('rejects expired snapshots and cross-staff, cross-company and changed lookups without fetching data', function () {
    $token = $this->postJson($this->url, $this->draft)->assertOk()->json('snapshot_token');
    $input = [...$this->draft, 'snapshot_token' => $token];
    $calls = Http::recorded()->count();
    $this->actingAs($this->makeCompanyAdmin())->postJson($this->url, $input)->assertUnprocessable()->assertJsonValidationErrors('snapshot_token');
    $this->actingAs($this->staff);
    $this->postJson(route('admin.drafts.studio-preview', $this->makeCompany()->uuid), $input)->assertUnprocessable()->assertJsonValidationErrors('snapshot_token');
    $this->postJson($this->url, [...$input, 'tracking_number' => 'OTHER'])->assertUnprocessable()->assertJsonValidationErrors('snapshot_token');
    $this->postJson($this->url, [...$input, 'search_option' => 'carrierPro'])->assertUnprocessable()->assertJsonValidationErrors('snapshot_token');
    $this->travel(16)->minutes();
    $this->postJson($this->url, $input)->assertUnprocessable()->assertJsonValidationErrors('snapshot_token');
    expect(Http::recorded()->count())->toBe($calls);
});
