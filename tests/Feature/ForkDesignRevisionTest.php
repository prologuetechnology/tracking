<?php

use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Models\DesignDraftRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCoreFixtures();
    $this->withoutVite();
    Http::preventStrayRequests();
    $this->actingAs($this->staff = $this->makeCompanyAdmin());
    $this->company = $this->makeCompany(['enable_map' => true]);
    $theme = $this->makeTheme();
    $settings = [
        'name' => 'Original proposal', 'layout' => 'journey.v1', 'palette' => 'company',
        'headline_style' => 'serif', 'theme_id' => $theme->id,
        'logo_image_id' => $this->makeImage('logo')->id, 'banner_image_id' => $this->makeImage('banner')->id,
        'footer_image_id' => $this->makeImage('footer')->id, 'sections' => ['enable_map'],
        'search_option' => 'carrierPro', 'tracking_number' => 'PRO-ORIGINAL',
        'section_orders' => ['journey.v1' => ['receiving', 'map', 'documents', 'instructions', 'references']],
    ];
    $save = app(SaveDesignDraft::class);
    $this->original = $save->execute($this->company, $settings, $this->staff->id);
    $this->revision = $this->original->revisions()->sole();
    $this->proof = $this->revision->proofs()->create(['snapshot' => [], 'shipment_reference' => 'PRO-ORIGINAL', 'created_by' => $this->staff->id]);
    $save->execute($this->company, [...$settings, 'layout' => 'record.v1', 'base_version' => 1, 'tracking_number' => 'PRO-LATER'], $this->staff->id, $this->original);
    $theme->delete();
    $this->fork = route('admin.drafts.fork', [$this->company->uuid, $this->original->uuid, 1]);
});

it('makes an independent named draft from the chosen revision with original assets, theme, lookup and only entitled sections', function (bool $legacy) {
    if ($legacy) {
        $olderDesign = $this->revision->design;
        $olderDesign['layout'] = 'answer.v1';
        unset($olderDesign['section_orders'], $olderDesign['presentation_version'], $olderDesign['headline_style'], $olderDesign['palette']);
        $this->revision->update(['design' => $olderDesign]);
    }
    $before = $this->revision->design;
    $this->company->update(['enable_map' => false]);
    $result = $this->post($this->fork, ['name' => 'Separate proposal']);
    $copy = $this->company->drafts()->where('name', 'Separate proposal')->sole();
    $result->assertRedirectToRoute('admin.drafts.show', [$this->company->uuid, $copy->uuid]);
    $new = $copy->revisions()->sole();

    expect($new->design)->toBe([...$before, 'sections' => []])
        ->and($new->version)->toBe(1)
        ->and($new->tracking_number)->toBe('PRO-ORIGINAL')
        ->and($new->search_option)->toBe('carrierPro')
        ->and($new->created_by)->toBe($this->staff->id)
        ->and($new->proofs()->count())->toBe(0)
        ->and($this->original->revisions()->count())->toBe(2)
        ->and($this->revision->fresh()->design)->toBe($before)
        ->and($this->revision->proofs()->sole()->uuid)->toBe($this->proof->uuid)
        ->and($this->company->publications()->count())->toBe(0);
    foreach (['logo_image_id', 'banner_image_id', 'footer_image_id'] as $field) {
        expect($new->$field)->toBe($this->revision->$field);
    }
    Http::assertNothingSent();
})->with(['current layout snapshot' => false, 'older Answer snapshot' => true]);

it('scopes revision forks to the staff permission, company, draft, revision and a supplied name', function () {
    $other = $this->makeCompany();
    $input = ['name' => 'Copy'];
    $this->post(route('admin.drafts.fork', [$other->uuid, $this->original->uuid, 1]), $input)->assertNotFound();
    $this->post(route('admin.drafts.fork', [$this->company->uuid, $this->original->uuid, 9]), $input)->assertNotFound();
    $this->post($this->fork, ['name' => ''])->assertSessionHasErrors('name');
    $this->actingAs($this->makeStandardUser())->post($this->fork, $input)->assertForbidden();
    auth()->logout();
    $this->post($this->fork, $input)->assertRedirectToRoute('login');
    expect(DesignDraftRevision::count())->toBe(2);
});
