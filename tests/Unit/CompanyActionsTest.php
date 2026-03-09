<?php

namespace Tests\Unit;

use App\Actions\Companies\CreateCompany;
use App\Actions\Companies\ListCompanies;
use App\Actions\Companies\SetCompanyImageAsset;
use App\Actions\Companies\SetCompanyTheme;
use App\Actions\Companies\ShowCompany;
use App\Actions\Companies\ToggleCompanyField;
use App\Actions\Companies\UpdateCompany;
use App\Enums\ImageTypeEnum;
use App\Models\CompanyFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CompanyActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_updates_and_lists_companies_with_expected_relations(): void
    {
        $theme = $this->makeTheme();
        $company = (new CreateCompany)->execute([
            'name' => 'Acme Logistics',
            'website' => 'https://acme.test',
            'phone' => '555-111-2222',
            'email' => 'ops@acme.test',
            'pipeline_company_id' => 4123,
            'theme_id' => $theme->id,
            'enable_map' => false,
            'enable_documents' => false,
        ]);

        $updated = (new UpdateCompany)->execute($company, [
            'name' => 'Acme Freight',
        ]);

        $listed = (new ListCompanies)->execute();
        $shown = (new ShowCompany)->execute($company);

        $this->assertSame('Acme Freight', $updated->name);
        $this->assertTrue($listed->first()->relationLoaded('theme'));
        $this->assertTrue($shown->relationLoaded('features'));
    }

    public function test_it_sets_theme_and_assets_and_toggles_boolean_fields(): void
    {
        $company = $this->makeCompany();
        $theme = $this->makeTheme();
        $logo = $this->makeImage(ImageTypeEnum::LOGO->value);

        $company = (new SetCompanyTheme)->execute($company, $theme->id);
        $company = (new SetCompanyImageAsset)->execute($company, $logo->id, ImageTypeEnum::LOGO->value);
        $company = (new ToggleCompanyField)->execute($company, 'enable_map');

        $this->assertSame($theme->id, $company->theme_id);
        $this->assertSame($logo->id, $company->logo_image_id);
        $this->assertTrue($company->enable_map);
    }

    public function test_it_rejects_invalid_company_asset_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SetCompanyImageAsset)->execute($this->makeCompany(), 1, 'invalid');
    }
}
