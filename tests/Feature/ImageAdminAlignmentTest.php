<?php

namespace Tests\Feature;

use App\Enums\ImageTypeEnum;
use App\Models\Company;
use App\Models\Image;
use App\Models\ImageType;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\ImageTypeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ImageAdminAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            ImageTypeSeeder::class,
        ]);
    }

    public function test_image_admin_page_is_hydrated_with_initial_images_and_types(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $logoType = $this->findImageType(ImageTypeEnum::LOGO->value);
        $bannerType = $this->findImageType(ImageTypeEnum::BANNER->value);

        Image::query()->create([
            'name' => 'Acme Logo',
            'image_type_id' => $logoType->id,
            'file_path' => 'images/acme-logo.png',
        ]);
        Image::query()->create([
            'name' => 'Acme Banner',
            'image_type_id' => $bannerType->id,
            'file_path' => 'images/acme-banner.png',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.image.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/image/Index')
                ->has('initialImages', 2)
                ->has('initialImageTypes', 5)
                ->where('initialImages.0.image_type.name', ImageTypeEnum::BANNER->value)
                ->where('initialImages.1.image_type.name', ImageTypeEnum::LOGO->value));
    }

    public function test_guests_and_unauthorized_users_cannot_access_image_api_endpoints(): void
    {
        Storage::fake('spaces');

        $imageType = $this->findImageType(ImageTypeEnum::LOGO->value);
        $image = Image::query()->create([
            'name' => 'Unauthorized Logo',
            'image_type_id' => $imageType->id,
            'file_path' => 'images/unauthorized-logo.png',
        ]);

        $this->getJson(route('api.images.index'))->assertUnauthorized();
        $this->postJson(route('api.images.store'), [])->assertUnauthorized();
        $this->deleteJson(route('api.images.destroy', $image))->assertUnauthorized();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.images.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('api.images.store'), [
                'name' => 'Forbidden Logo',
                'image_type_id' => $imageType->id,
                'image' => UploadedFile::fake()->image('forbidden.png'),
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson(route('api.images.destroy', $image))
            ->assertForbidden();
    }

    public function test_authorized_users_can_upload_images_and_failed_submissions_are_rejected(): void
    {
        Storage::fake('spaces');

        $superAdmin = $this->createSuperAdmin();
        $imageType = $this->findImageType(ImageTypeEnum::LOGO->value);
        $file = UploadedFile::fake()->image('acme-logo.png');

        $this->actingAs($superAdmin)
            ->postJson(route('api.images.store'), [
                'name' => 'Acme Logo',
                'image_type_id' => $imageType->id,
                'image' => $file,
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Acme Logo')
            ->assertJsonPath('image_type.id', $imageType->id);

        Storage::disk('spaces')->assertExists('images/'.$file->hashName());
        $this->assertDatabaseHas('images', [
            'name' => 'Acme Logo',
            'image_type_id' => $imageType->id,
        ]);

        $this->actingAs($superAdmin)
            ->postJson(route('api.images.store'), [
                'name' => 'Broken Upload',
                'image_type_id' => $imageType->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_images_can_be_filtered_by_image_type_id(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $logoType = $this->findImageType(ImageTypeEnum::LOGO->value);
        $bannerType = $this->findImageType(ImageTypeEnum::BANNER->value);

        Image::query()->create([
            'name' => 'Logo',
            'image_type_id' => $logoType->id,
            'file_path' => 'images/logo.png',
        ]);
        Image::query()->create([
            'name' => 'Banner',
            'image_type_id' => $bannerType->id,
            'file_path' => 'images/banner.png',
        ]);

        $this->actingAs($superAdmin)
            ->getJson(route('api.images.index', [
                'image_type_id' => $bannerType->id,
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Banner')
            ->assertJsonPath('0.image_type.name', ImageTypeEnum::BANNER->value);
    }

    public function test_deleting_images_clears_related_company_asset_references(): void
    {
        Storage::fake('spaces');

        $superAdmin = $this->createSuperAdmin();
        $logo = $this->createImage(ImageTypeEnum::LOGO->value, 'Logo');
        $banner = $this->createImage(ImageTypeEnum::BANNER->value, 'Banner');
        $footer = $this->createImage(ImageTypeEnum::FOOTER->value, 'Footer');

        $company = $this->createCompany();
        $company->forceFill([
            'logo_image_id' => $logo->id,
            'banner_image_id' => $banner->id,
            'footer_image_id' => $footer->id,
        ])->save();

        $this->actingAs($superAdmin)
            ->deleteJson(route('api.images.destroy', $logo))
            ->assertNoContent();

        $company->refresh();
        $this->assertNull($company->logo_image_id);
        $this->assertSame($banner->id, $company->banner_image_id);
        $this->assertSame($footer->id, $company->footer_image_id);

        $this->actingAs($superAdmin)
            ->deleteJson(route('api.images.destroy', $banner))
            ->assertNoContent();

        $company->refresh();
        $this->assertNull($company->banner_image_id);
        $this->assertSame($footer->id, $company->footer_image_id);

        $this->actingAs($superAdmin)
            ->deleteJson(route('api.images.destroy', $footer))
            ->assertNoContent();

        $company->refresh();
        $this->assertNull($company->footer_image_id);
    }

    private function createSuperAdmin(): User
    {
        $user = User::factory()->create(['email' => 'image-admin@example.com']);
        $user->syncRoles(['Super Admin']);

        return $user;
    }

    private function findImageType(string $name): ImageType
    {
        return ImageType::query()->where('name', $name)->firstOrFail();
    }

    private function createImage(string $type, string $name): Image
    {
        return Image::query()->create([
            'name' => $name,
            'image_type_id' => $this->findImageType($type)->id,
            'file_path' => 'images/'.str($name)->slug()->value().'.png',
        ])->load('imageType');
    }

    private function createCompany(): Company
    {
        $theme = Theme::query()->create([
            'name' => 'Image Theme '.str()->uuid(),
            'colors' => [
                'root' => [
                    'background' => '0 0% 100%',
                    'foreground' => '240 10% 4%',
                    'primary' => '240 6% 10%',
                    'primaryForeground' => '0 0% 98%',
                    'secondary' => '240 5% 96%',
                    'secondaryForeground' => '240 6% 10%',
                    'accent' => '200 50% 50%',
                    'accentForeground' => '0 0% 98%',
                    'destructive' => '0 84% 60%',
                    'destructiveForeground' => '0 0% 98%',
                    'muted' => '240 5% 96%',
                    'mutedForeground' => '240 4% 46%',
                    'popover' => '0 0% 100%',
                    'popoverForeground' => '240 10% 4%',
                    'card' => '0 0% 100%',
                    'cardForeground' => '240 10% 4%',
                    'border' => '240 6% 90%',
                    'input' => '240 6% 90%',
                    'ring' => '240 10% 4%',
                ],
            ],
            'radius' => '0.5rem',
            'is_system' => true,
            'derive_from' => 'primary',
        ]);

        return Company::query()->create([
            'name' => 'Image Company',
            'website' => 'https://image-company.test',
            'phone' => '555-000-1111',
            'email' => 'images@test.local',
            'pipeline_company_id' => 9010,
            'theme_id' => $theme->id,
            'requires_brand' => false,
        ]);
    }
}
