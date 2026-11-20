<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DesignDraft;
use App\Support\Testing\FakePipelinePayloads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DesignDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCoreFixtures();
        config(['services.pipeline.api_url' => 'https://pipeline.example/api', 'services.pipeline.base_url' => 'https://pipeline.example']);
        Http::preventStrayRequests();
    }

    private function settings(array $changes = []): array
    {
        return [...[
            'name' => 'Autumn review', 'theme_id' => null, 'logo_image_id' => null,
            'banner_image_id' => null, 'footer_image_id' => null, 'sections' => [],
            'search_option' => 'bol', 'tracking_number' => 'BOL123',
        ], ...$changes];
    }

    private function save(Company $company, array $changes = []): DesignDraft
    {
        $this->post(route('admin.drafts.store', $company->uuid), $this->settings($changes))->assertSessionHasNoErrors();
        $draft = $company->drafts()->sole();
        $this->assertDatabaseHas('design_drafts', ['id' => $draft->id, 'company_id' => $company->id]);

        return $draft;
    }

    private function frame(Company $company, DesignDraft $draft, int $version = 1): string
    {
        return route('admin.drafts.render', [$company->uuid, $draft->uuid, $version, 'trackingNumber' => 'BOL123', 'searchOption' => 'bol']);
    }

    public function test_staff_can_reopen_a_named_draft_and_preview_a_frozen_revision_with_current_shipment_data(): void
    {
        $this->actingAs($user = $this->makeCompanyAdmin());
        $company = $this->makeCompany(['pipeline_company_id' => 1001, 'enable_documents' => true]);
        $banner = $this->makeImage('banner', ['file_path' => 'images/preview.jpg']);
        $theme = $this->makeTheme();
        $originalColors = $theme->colors;
        $draft = $this->save($company, ['banner_image_id' => $banner->id, 'theme_id' => $theme->id]);
        $this->assertSame($user->id, $draft->revisions()->sole()->created_by);
        $editor = route('admin.drafts.show', [$company->uuid, $draft->uuid]);
        $this->get(route('admin.drafts.index', $company->uuid))->assertInertia(fn (Assert $p) => $p
            ->where('drafts.data.0.name', 'Autumn review')->where('drafts.data.0.revisions_count', 1));
        $this->get($editor)->assertInertia(fn (Assert $p) => $p->where('initialValues.banner_image_id', $banner->id)
            ->where('initialValues.tracking_number', 'BOL123')->where('initialValues.base_version', 1));
        $theme->update(['colors' => $this->makeTheme()->colors]);
        $changes = $this->settings(['theme_id' => $theme->id, 'base_version' => 1]);
        $this->put($editor, $changes)->assertRedirect($editor);
        $this->put($editor, $changes)->assertSessionHasErrors('base_version');
        $this->assertSame(2, $draft->revisions()->count());

        $makePayload = function (string $carrier) {
            $payload = FakePipelinePayloads::shipmentSearch('BOL123');
            $payload['data'][0]['carrierName'] = $carrier;

            return $payload;
        };
        Http::fake(['*/shipmentSearch' => Http::sequence()->push($makePayload('First scan'))
            ->push($makePayload('Fresh scan'))->push($makePayload('Public scan'))]);
        $this->get($this->frame($company, $draft))->assertInertia(fn (Assert $p) => $p
            ->component('brandedTracking/Classic')->where('isDraftPreview', true)
            ->where('initialCompany.banner.file_path', 'images/preview.jpg')->where('initialCompany.theme.colors.root.primary', $originalColors['root']['primary'])
            ->where('initialTrackingData.carrierName', 'First scan')->missing('initialCompany.api_token'));
        $this->get($this->frame($company, $draft, 2))->assertInertia(fn (Assert $p) => $p
            ->where('initialCompany.banner', null)->where('initialCompany.theme.colors.root.primary', $originalColors['root']['primary'])
            ->where('initialTrackingData.carrierName', 'Fresh scan'));
        $this->app['auth']->guard()->logout();
        $this->get('/trackShipment?searchOption=bol&trackingNumber=BOL123&draft='.$draft->uuid)
            ->assertInertia(fn (Assert $p) => $p->component('brandedTracking/Classic')
                ->where('initialCompany.banner', null)->where('initialCompany.theme_id', $company->theme_id)
                ->where('initialCompany.enable_documents', true)->missing('isDraftPreview'));
        $this->assertNull($company->fresh()->banner_image_id);
    }

    public function test_guest_and_read_only_users_cannot_read_save_or_preview_drafts(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeCompanyAdmin());
        $draft = $this->save($company);
        $paths = [
            route('admin.drafts.index', $company->uuid), route('admin.drafts.create', $company->uuid),
            route('admin.drafts.show', [$company->uuid, $draft->uuid]),
            route('admin.drafts.preview', [$company->uuid, $draft->uuid, 1]), $this->frame($company, $draft),
        ];
        $this->app['auth']->guard()->logout();
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
        $this->post($paths[0], $this->settings())->assertRedirect(route('login'));
        $this->actingAs($this->makeUserWithPermission('company:show'));
        foreach ($paths as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->post($paths[0], $this->settings())->assertForbidden();
        $this->put($paths[2], $this->settings(['base_version' => 1]))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_preview_scope_rejects_foreign_drafts_and_shipments_before_fetching_documents_or_maps(): void
    {
        $this->actingAs($this->makeCompanyAdmin());
        $company = $this->makeCompany(['pipeline_company_id' => 1001]);
        $other = $this->makeCompany(['pipeline_company_id' => 2002, 'enable_map' => true]);
        $draft = $this->save($company);
        $this->get($this->frame($other, $draft))->assertNotFound();
        $this->get($this->frame($company, $draft, 99))->assertNotFound();
        Http::assertNothingSent();
        Http::fake(['*/shipmentSearch' => Http::response(FakePipelinePayloads::shipmentSearch('BOL123', 2002))]);
        $this->get($this->frame($company, $draft))->assertInertia(fn (Assert $p) => $p
            ->component('admin/designDrafts/Unavailable')->missing('initialTrackingData')->missing('initialCompany'));
        Http::assertSentCount(1);
    }

    public function test_draft_features_respect_current_company_access_and_shared_pipeline_brand(): void
    {
        $this->actingAs($this->makeCompanyAdmin());
        $this->makeCompany(['pipeline_company_id' => 1001, 'requires_brand' => true, 'brand' => 'OTHER']);
        $company = $this->makeCompany(['pipeline_company_id' => 1001, 'requires_brand' => true, 'brand' => 'TARGET', 'enable_map' => true, 'enable_documents' => true]);
        $this->makeCompanyApiToken($company);
        $draft = $this->save($company, ['sections' => ['enable_documents', 'enable_map']]);
        $company->update(['enable_documents' => false]);
        $this->fakeTrackingApisFixture($company);
        $this->get($this->frame($company, $draft))->assertInertia(fn (Assert $p) => $p
            ->where('initialCompany.name', $company->name)->has('initialShipmentCoordinates', 1)
            ->has('initialShipmentDocuments', 0)->has('initialCompany.features', 1)
            ->missing('initialTrackingData.poNumbers')->missing('initialTrackingData.specialInstructions'));
    }

    public function test_preview_skips_optional_upstream_requests_for_sections_hidden_in_the_draft(): void
    {
        $this->actingAs($this->makeCompanyAdmin());
        $company = $this->makeCompany(['pipeline_company_id' => 1001, 'enable_map' => true, 'enable_documents' => true]);
        $this->makeCompanyApiToken($company);
        $draft = $this->save($company, ['sections' => []]);
        Http::fake(['*/shipmentSearch' => Http::response(FakePipelinePayloads::shipmentSearch('BOL123'))]);
        $this->get($this->frame($company, $draft))->assertInertia(fn (Assert $p) => $p
            ->component('brandedTracking/Classic')->where('initialShipmentCoordinates', null)->has('initialShipmentDocuments', 0));
        Http::assertSentCount(1);
    }

    public function test_invalid_settings_rollback_and_saved_assets_survive_library_deletion_attempts(): void
    {
        $this->actingAs($this->makeSuperAdmin());
        $company = $this->makeCompany();
        $logo = $this->makeImage('logo', ['file_path' => 'images/kept.png']);
        $store = route('admin.drafts.store', $company->uuid);
        $this->post($store, $this->settings(['name' => ' ']))->assertSessionHasErrors('name');
        $this->post($store, $this->settings(['banner_image_id' => $logo->id]))->assertSessionHasErrors('banner_image_id');
        $this->post($store, $this->settings(['theme_id' => 99999]))->assertSessionHasErrors('theme_id');
        $this->assertDatabaseCount('design_drafts', 0);
        $draft = $this->save($company, ['logo_image_id' => $logo->id]);
        $this->put(route('admin.drafts.update', [$company->uuid, $draft->uuid]), $this->settings(['base_version' => 1]));
        $disk = Storage::fake(config('filesystems.image_library_disk'));
        $disk->put($logo->file_path, 'fixture');
        $this->deleteJson(route('api.images.destroy', $logo->id))->assertUnprocessable()->assertJsonValidationErrors('image');
        $disk->assertExists($logo->file_path);
        $this->assertDatabaseHas('images', ['id' => $logo->id]);
    }
}
