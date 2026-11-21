<?php

use App\Actions\DesignDrafts\RenderProofImages;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Jobs\RenderDesignProof;
use App\Models\DesignProof;
use App\Support\Testing\FakePipelinePayloads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCoreFixtures();
    config(['services.pipeline.api_url' => 'https://pipeline.example/api']);
    Http::preventStrayRequests();
    Queue::fake();
    Storage::fake('local');
    $this->actingAs($this->staff = $this->makeCompanyAdmin());
    $this->company = $this->makeCompany(['pipeline_company_id' => 1001]);
    $this->settings = [
        'name' => 'Timing review', 'layout' => 'answer.v1', 'theme_id' => null, 'logo_image_id' => null,
        'banner_image_id' => null, 'footer_image_id' => null, 'sections' => [], 'search_option' => 'bol',
    ];
    $this->draft = app(SaveDesignDraft::class)->execute($this->company, $this->settings, $this->staff->id);
    $this->revision = $this->draft->revisions()->sole();
    $this->params = [$this->company->uuid, $this->draft->uuid, 1];
    $this->lookup = ['trackingNumber' => 'BOL123', 'searchOption' => 'bol'];
});

it('captures one private snapshot, retains both bytes, publishes only the approved revision, and restores Classic', function () {
    $first = FakePipelinePayloads::shipmentSearch('BOL123');
    $later = $first;
    $later['data'][0]['carrierName'] = 'Updated carrier';
    Http::fake(['*/shipmentSearch' => Http::sequence()->push($first)->whenEmpty(Http::response($later))]);
    $public = route('trackShipment.index', $this->lookup);
    $this->post(route('admin.drafts.proofs.store', $this->params), $this->lookup)->assertSessionHasNoErrors();
    $proof = $this->revision->proofs()->sole();
    $args = [...$this->params, $proof->uuid];
    $approve = route('admin.drafts.proofs.approve', $args);
    $publish = route('admin.drafts.proofs.publish', $args);
    $download = fn ($view) => route('admin.drafts.proofs.download', [...$args, $view]);
    Queue::assertPushed(RenderDesignProof::class, 1);
    $this->get($download('mobile'))->assertNotFound();
    $this->post($approve, ['customer_approver' => 'Receiving manager'])->assertConflict();
    expect($proof->getRawOriginal('snapshot'))->not->toContain('Acme Carrier');
    $images = ['desktop' => 'original desktop bytes', 'mobile' => 'original mobile bytes'];
    $renderer = Mockery::mock(RenderProofImages::class);
    $renderer->shouldReceive('execute')->once()->andReturnUsing(function ($page, $path) use ($images) {
        expect($page['props']['initialTrackingData']['carrierName'])->toBe('Acme Carrier');
        expect($page['props'])->not->toHaveKey('auth');
        foreach ($images as $view => $bytes) {
            file_put_contents($path.'/'.$view.'.png', $bytes);
        }

        return array_map(fn ($bytes) => hash('sha256', $bytes), $images);
    });
    $job = new RenderDesignProof($proof->id);
    $job->handle($renderer);
    $job->handle($renderer);
    Http::assertSentCount(1);
    $this->post($publish, ['publication' => 0])->assertConflict();
    $this->post($approve, ['customer_approver' => 'Receiving manager'])->assertRedirect();
    $this->post($approve, ['customer_approver' => 'Another person'])->assertRedirect();
    expect($proof->fresh()->customer_approver)->toBe('Receiving manager');
    $this->get($public)->assertInertia(fn (Assert $p) => $p->component('brandedTracking/Classic'));
    app(SaveDesignDraft::class)->execute($this->company, [...$this->settings, 'layout' => 'classic.v1', 'base_version' => 1], $this->staff->id, $this->draft);
    $this->post($publish, ['publication' => 0])->assertRedirect();
    $this->get($public)->assertInertia(fn (Assert $p) => $p->component('brandedTracking/Answer')
        ->where('initialTrackingData.carrierName', 'Updated carrier')->missing('frozenAt')->missing('proofs')->missing('initialTrackingData.poNumbers'));
    $this->get(route('admin.drafts.preview', $this->params))->assertInertia(fn (Assert $p) => $p
        ->where('activeProof', $proof->uuid)->where('proofs.0.uuid', $proof->uuid)->missing('proofs.0.snapshot'));
    foreach ($images as $view => $bytes) {
        $response = $this->get($download($view))->assertOk()->assertHeader('Content-Type', 'image/png');
        expect($response->streamedContent())->toBe($bytes);
        expect($proof->fresh()->checksums[$view])->toBe(hash('sha256', $bytes));
    }
    Storage::disk('local')->put($proof->fresh()->directory.'/mobile.png', 'damaged bytes');
    $this->get($download('mobile'))->assertServiceUnavailable();
    $originalTheme = $this->company->theme_id;
    $this->company->update(['theme_id' => $this->makeTheme()->id]);
    $current = $this->company->publications()->sole()->id;
    $this->post($publish, ['publication' => 0])->assertSessionHasErrors('publication');
    $restore = route('admin.drafts.rollback', $this->company->uuid);
    $this->post($restore, ['publication' => $current, 'restore' => 0])->assertRedirect();
    $this->get($public)->assertInertia(fn (Assert $p) => $p->component('brandedTracking/Classic')->where('initialCompany.theme.id', $originalTheme));
    $this->post($restore, ['publication' => $this->company->publications()->max('id'), 'restore' => $current])->assertRedirect();
    $this->get($public)->assertInertia(fn (Assert $p) => $p->component('brandedTracking/Answer'));
    $this->company->delete();
    expect(DesignProof::count())->toBe(0);
    Storage::disk('local')->assertMissing(DesignProof::companyDirectory($this->company->id));
});

it('enforces staff access and company, revision and shipment ownership at every proof entry point', function () {
    Http::fake(['*/shipmentSearch' => Http::response(FakePipelinePayloads::shipmentSearch('BOL123', 999))]);
    $capture = route('admin.drafts.proofs.store', $this->params);
    $this->post($capture, $this->lookup)->assertSessionHasErrors('trackingNumber');
    expect(DesignProof::count())->toBe(0);
    $proof = $this->revision->proofs()->create(['snapshot' => [], 'shipment_reference' => 'test', 'created_by' => $this->staff->id]);
    app(SaveDesignDraft::class)->execute($this->company, [...$this->settings, 'base_version' => 1], $this->staff->id, $this->draft);
    $other = $this->makeCompany();
    foreach ([[$other->uuid, $this->draft->uuid, 1], [$this->company->uuid, $this->draft->uuid, 2]] as $params) {
        $args = [...$params, $proof->uuid];
        $this->get(route('admin.drafts.proofs.download', [...$args, 'desktop']))->assertNotFound();
        $this->post(route('admin.drafts.proofs.approve', $args))->assertNotFound();
        $this->post(route('admin.drafts.proofs.publish', $args))->assertNotFound();
    }
    $args = [...$this->params, $proof->uuid];
    $routes = [
        ['post', $capture], ['post', route('admin.drafts.proofs.retry', $args)], ['get', route('admin.drafts.proofs.download', [...$args, 'desktop'])],
        ['post', route('admin.drafts.proofs.approve', $args)], ['post', route('admin.drafts.proofs.publish', $args)],
        ['post', route('admin.drafts.rollback', $this->company->uuid)],
    ];
    $this->app['auth']->guard()->logout();
    foreach ($routes as [$verb, $url]) {
        $this->{$verb}($url)->assertRedirect(route('login'));
    }
    $this->actingAs($this->makeUserWithPermission('company:show'));
    foreach ($routes as [$verb, $url]) {
        $this->{$verb}($url)->assertForbidden();
    }
    Http::assertSentCount(1);
});

it('cleans up partial image pairs and keeps a failed capture unavailable', function () {
    $proof = $this->revision->proofs()->create(['snapshot' => [], 'shipment_reference' => 'failure', 'created_by' => $this->staff->id]);
    $renderer = Mockery::mock(RenderProofImages::class);
    $renderer->shouldReceive('execute')->andReturnUsing(function ($page, $path) {
        file_put_contents($path.'/desktop.png', 'partial');
        throw new RuntimeException('Mobile render failed');
    });
    $job = new RenderDesignProof($proof->id);
    expect(fn () => $job->handle($renderer))->toThrow(RuntimeException::class);
    $job->failed(null);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    expect($proof->fresh()->status)->toBe('failed');
    $this->get(route('admin.drafts.proofs.download', [...$this->params, $proof->uuid, 'desktop']))->assertNotFound();
});

it('retries the original capture and ignores late completion or failure from an older attempt', function () {
    $proof = $this->revision->proofs()->create(['snapshot' => ['unchanged' => true], 'shipment_reference' => 'old', 'created_by' => $this->staff->id]);
    $old = new RenderDesignProof($proof->id);
    $old->failed(null);
    $this->post(route('admin.drafts.proofs.retry', [...$this->params, $proof->uuid]))->assertRedirect();
    Queue::assertPushed(RenderDesignProof::class, fn ($job) => $job->generation === 2);
    $renderer = Mockery::mock(RenderProofImages::class);
    $renderer->shouldNotReceive('execute');
    $old->handle($renderer);
    $old->failed(null);
    expect($proof->refresh()->snapshot)->toBe(['unchanged' => true]);
    expect($proof->status)->toBe('pending');
    Http::assertNothingSent();
});
