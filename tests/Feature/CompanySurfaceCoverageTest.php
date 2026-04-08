<?php

namespace Tests\Feature;

use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanySurfaceCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_pages_and_api_support_full_crud_and_toggle_flows(): void
    {
        $this->seedCoreFixtures(withImageTypes: true);

        $theme = $this->makeTheme(['name' => 'Surface Theme']);
        $company = $this->makeCompany(['theme_id' => $theme->id, 'enable_map' => false, 'enable_documents' => false]);
        $logo = $this->makeImage(ImageTypeEnum::LOGO->value);
        $banner = $this->makeImage(ImageTypeEnum::BANNER->value);
        $viewer = $this->makeUserWithPermission('company:show');
        $creator = $this->makeUserWithPermission('company:store');
        $updater = $this->makeUserWithPermission('company:update');
        $destroyer = $this->makeUserWithPermission('company:destroy');

        $this->actingAs($viewer)
            ->get(route('admin.companies.show', $company->uuid))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/companies/Edit')
                ->where('companyInitialValues.name', $company->name)
                ->has('companyFeaturesInitialValues', 2)
                ->has('initialImageTypes', 5)
                ->where('initialImageTypes.0.name', ImageTypeEnum::LOGO->value));

        $created = $this->actingAs($creator)
            ->postJson(route('api.companies.store'), [
                'name' => 'Created Company',
                'website' => 'https://created.test',
                'phone' => '555-123-4567',
                'email' => 'created@example.test',
                'pipeline_company_id' => 907788,
                'theme_id' => $theme->id,
                'requires_brand' => true,
                'brand' => 'CREATED',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Created Company')
            ->json();

        $this->actingAs($updater)
            ->patchJson(route('api.companies.setTheme', $company), [
                'theme_id' => $theme->id,
            ])
            ->assertOk()
            ->assertJsonPath('theme.id', $theme->id);

        $this->actingAs($updater)
            ->patchJson(route('api.companies.setImageAsset', $company), [
                'image_id' => $logo->id,
                'type' => ImageTypeEnum::LOGO->value,
            ])
            ->assertOk()
            ->assertJsonPath('logo.id', $logo->id);

        $this->actingAs($updater)
            ->patchJson(route('api.companies.clearImageAsset', $company), [
                'type' => ImageTypeEnum::LOGO->value,
            ])
            ->assertOk()
            ->assertJsonPath('logo', null);

        $this->actingAs($updater)
            ->patchJson(route('api.companies.toggleMapOption', $company))
            ->assertOk()
            ->assertJsonPath('enable_map', true);

        $this->actingAs($updater)
            ->patchJson(route('api.companies.toggleDocumentsOption', $company))
            ->assertOk()
            ->assertJsonPath('enable_documents', true);

        $this->actingAs($updater)
            ->patchJson(route('api.companies.toggleActive', $company))
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->actingAs($viewer)
            ->getJson(route('api.companies.show', $company))
            ->assertOk()
            ->assertJsonPath('id', $company->id)
            ->assertJsonPath('theme.id', $theme->id);

        $this->actingAs($updater)
            ->patchJson(route('api.companies.setImageAsset', $company), [
                'image_id' => $banner->id,
                'type' => ImageTypeEnum::LOGO->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_id']);

        $this->actingAs($destroyer)
            ->deleteJson(route('api.companies.destroy', $created['id']))
            ->assertNoContent();

        $this->assertDatabaseMissing('companies', ['id' => $created['id']]);
    }

    public function test_company_feature_endpoints_return_expected_shapes_and_sync_legacy_fields(): void
    {
        $this->seedCoreFixtures();

        $company = $this->makeCompany(['enable_map' => false, 'enable_documents' => false]);
        $user = $this->makeStandardUser();

        $this->actingAs($user)
            ->getJson(route('api.companies.features.index'))
            ->assertOk()
            ->assertJsonCount(2);

        $this->actingAs($user)
            ->getJson(route('api.companies.features.show', $company))
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($user)
            ->putJson(route('api.companies.features.sync', $company), [
                'features' => ['enable_map'],
            ])
            ->assertOk()
            ->assertJsonPath('features.0.slug', 'enable_map');

        $company->refresh();
        $this->assertTrue($company->enable_map);
        $this->assertFalse($company->enable_documents);

        $this->actingAs($user)
            ->patchJson(route('api.companies.features.toggle', [
                'company' => $company->id,
                'feature' => 'enable_documents',
            ]))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'enable_documents']);

        $this->assertTrue($company->fresh()->enable_documents);
    }

    public function test_company_api_routes_enforce_permissions(): void
    {
        $this->seedCoreFixtures(withImageTypes: true);

        $company = $this->makeCompany();
        $theme = $this->makeTheme();
        $logo = $this->makeImage();
        $user = $this->makeStandardUser();

        $this->actingAs($user)->postJson(route('api.companies.store'), [])->assertForbidden();
        $this->actingAs($user)->patchJson(route('api.companies.setTheme', $company), ['theme_id' => $theme->id])->assertForbidden();
        $this->actingAs($user)->patchJson(route('api.companies.setImageAsset', $company), ['image_id' => $logo->id, 'type' => ImageTypeEnum::LOGO->value])->assertForbidden();
        $this->actingAs($user)->patchJson(route('api.companies.clearImageAsset', $company), ['type' => ImageTypeEnum::LOGO->value])->assertForbidden();
        $this->actingAs($user)->deleteJson(route('api.companies.destroy', $company))->assertForbidden();
    }

    public function test_a_shared_image_can_be_assigned_to_multiple_companies_without_duplication(): void
    {
        $this->seedCoreFixtures(withImageTypes: true);

        $sharedLogo = $this->makeImage(ImageTypeEnum::LOGO->value);
        $firstCompany = $this->makeCompany();
        $secondCompany = $this->makeCompany([
            'pipeline_company_id' => 908001,
            'name' => 'Second Company',
            'email' => 'second@example.test',
        ]);
        $updater = $this->makeUserWithPermission('company:update');

        $this->actingAs($updater)
            ->patchJson(route('api.companies.setImageAsset', $firstCompany), [
                'image_id' => $sharedLogo->id,
                'type' => ImageTypeEnum::LOGO->value,
            ])
            ->assertOk();

        $this->actingAs($updater)
            ->patchJson(route('api.companies.setImageAsset', $secondCompany), [
                'image_id' => $sharedLogo->id,
                'type' => ImageTypeEnum::LOGO->value,
            ])
            ->assertOk();

        $firstCompany->refresh();
        $secondCompany->refresh();

        $this->assertSame($sharedLogo->id, $firstCompany->logo_image_id);
        $this->assertSame($sharedLogo->id, $secondCompany->logo_image_id);
        $this->assertSame(1, $sharedLogo->newQuery()->whereKey($sharedLogo->id)->count());
    }

    public function test_duplicate_pipeline_company_id_creation_converts_the_active_group_to_branded_tracking(): void
    {
        $this->seedCoreFixtures();

        $theme = $this->makeTheme();
        $existingCompany = $this->makeCompany([
            'pipeline_company_id' => 742,
            'brand' => null,
            'requires_brand' => false,
            'theme_id' => $theme->id,
        ]);
        $creator = $this->makeUserWithPermission('company:store');

        $created = $this->actingAs($creator)
            ->postJson(route('api.companies.store'), [
                'name' => 'Duplicate Pipeline Company',
                'website' => 'https://duplicate.test',
                'phone' => '555-000-7420',
                'email' => 'duplicate@example.test',
                'pipeline_company_id' => 742,
                'theme_id' => $theme->id,
                'requires_brand' => false,
                'brand' => ' newco ',
                'sibling_brand_assignments' => [
                    [
                        'company_id' => $existingCompany->id,
                        'brand' => ' legacy ',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('pipeline_company_id', 742)
            ->assertJsonPath('requires_brand', true)
            ->assertJsonPath('brand', 'NEWCO')
            ->json();

        $this->assertDatabaseHas('companies', [
            'id' => $existingCompany->id,
            'requires_brand' => true,
            'brand' => 'LEGACY',
        ]);
        $this->assertDatabaseHas('companies', [
            'id' => $created['id'],
            'requires_brand' => true,
            'brand' => 'NEWCO',
        ]);
    }

    public function test_duplicate_pipeline_company_id_creation_requires_missing_sibling_brands(): void
    {
        $this->seedCoreFixtures();

        $theme = $this->makeTheme();
        $this->makeCompany([
            'pipeline_company_id' => 742,
            'brand' => null,
            'requires_brand' => false,
            'theme_id' => $theme->id,
        ]);
        $creator = $this->makeUserWithPermission('company:store');

        $this->actingAs($creator)
            ->postJson(route('api.companies.store'), [
                'name' => 'Duplicate Pipeline Company',
                'website' => 'https://duplicate.test',
                'phone' => '555-000-7420',
                'email' => 'duplicate@example.test',
                'pipeline_company_id' => 742,
                'theme_id' => $theme->id,
                'requires_brand' => false,
                'brand' => 'NEWCO',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sibling_brand_assignments']);
    }

    public function test_duplicate_pipeline_company_id_group_brands_must_be_unique(): void
    {
        $this->seedCoreFixtures();

        $theme = $this->makeTheme();
        $this->makeCompany([
            'pipeline_company_id' => 742,
            'brand' => 'ACME',
            'requires_brand' => true,
            'theme_id' => $theme->id,
        ]);
        $creator = $this->makeUserWithPermission('company:store');

        $this->actingAs($creator)
            ->postJson(route('api.companies.store'), [
                'name' => 'Duplicate Pipeline Company',
                'website' => 'https://duplicate.test',
                'phone' => '555-000-7420',
                'email' => 'duplicate@example.test',
                'pipeline_company_id' => 742,
                'theme_id' => $theme->id,
                'requires_brand' => true,
                'brand' => ' acme ',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brand']);
    }

    public function test_updating_a_company_into_a_duplicate_pipeline_company_id_group_converts_the_group(): void
    {
        $this->seedCoreFixtures();

        $theme = $this->makeTheme();
        $existingCompany = $this->makeCompany([
            'pipeline_company_id' => 742,
            'brand' => null,
            'requires_brand' => false,
            'theme_id' => $theme->id,
        ]);
        $movingCompany = $this->makeCompany([
            'pipeline_company_id' => 743,
            'brand' => null,
            'requires_brand' => false,
            'theme_id' => $theme->id,
            'email' => 'moving@example.test',
        ]);
        $updater = $this->makeUserWithPermission('company:update');

        $this->actingAs($updater)
            ->patchJson(route('api.companies.update', $movingCompany), [
                'name' => $movingCompany->name,
                'website' => $movingCompany->website,
                'phone' => $movingCompany->phone,
                'email' => $movingCompany->email,
                'pipeline_company_id' => 742,
                'theme_id' => $theme->id,
                'requires_brand' => false,
                'brand' => 'moving',
                'sibling_brand_assignments' => [
                    [
                        'company_id' => $existingCompany->id,
                        'brand' => 'existing',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('pipeline_company_id', 742)
            ->assertJsonPath('requires_brand', true)
            ->assertJsonPath('brand', 'MOVING');

        $this->assertDatabaseHas('companies', [
            'id' => $existingCompany->id,
            'pipeline_company_id' => 742,
            'requires_brand' => true,
            'brand' => 'EXISTING',
        ]);
    }
}
