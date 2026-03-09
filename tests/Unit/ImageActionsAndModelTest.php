<?php

namespace Tests\Unit;

use App\Actions\Images\DeleteImage;
use App\Actions\Images\ListImages;
use App\Actions\Images\ListImageTypes;
use App\Actions\Images\StoreImage;
use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageActionsAndModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures(withImageTypes: true);
    }

    public function test_it_lists_images_and_image_types_and_can_store_and_delete_images(): void
    {
        Storage::fake('spaces');

        $logo = $this->makeImage(ImageTypeEnum::LOGO->value);
        $this->makeImage(ImageTypeEnum::BANNER->value);

        $filtered = (new ListImages)->execute($logo->image_type_id);
        $types = (new ListImageTypes)->execute();

        $stored = (new StoreImage)->execute([
            'name' => 'Stored Logo',
            'image_type_id' => $logo->image_type_id,
            'image' => UploadedFile::fake()->image('logo.png'),
        ]);

        (new DeleteImage)->execute($stored);

        $this->assertCount(1, $filtered);
        $this->assertGreaterThanOrEqual(5, $types->count());
        Storage::disk('spaces')->assertMissing($stored->file_path);
        $this->assertDatabaseMissing('images', ['id' => $stored->id]);
    }

    public function test_deleting_images_clears_company_asset_references(): void
    {
        $company = $this->makeCompany();
        $logo = $this->makeImage(ImageTypeEnum::LOGO->value);
        $banner = $this->makeImage(ImageTypeEnum::BANNER->value);
        $footer = $this->makeImage(ImageTypeEnum::FOOTER->value);

        $company->forceFill([
            'logo_image_id' => $logo->id,
            'banner_image_id' => $banner->id,
            'footer_image_id' => $footer->id,
        ])->save();

        $logo->delete();
        $banner->delete();
        $footer->delete();

        $company->refresh();

        $this->assertNull($company->logo_image_id);
        $this->assertNull($company->banner_image_id);
        $this->assertNull($company->footer_image_id);
    }
}
