<?php

namespace Tests\Browser;

use App\Enums\ImageTypeEnum;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DuskTestCase;

class ClassicTrackingLayoutTest extends DuskTestCase
{
    public static function viewports(): array
    {
        return [
            'desktop' => [1440, 900],
            'mobile' => [390, 844],
        ];
    }

    #[DataProvider('viewports')]
    public function test_existing_guest_link_keeps_its_brand_and_refreshes_the_same_shipment(int $width, int $height): void
    {
        $this->seedCoreFixtures();
        $theme = $this->makeTheme();
        $assets = [];

        foreach ([ImageTypeEnum::LOGO, ImageTypeEnum::BANNER, ImageTypeEnum::FOOTER] as $type) {
            $assets[$type->value.'_image_id'] = $this->makeImage($type->value, [
                'file_path' => 'images/target-'.$type->value.'.png',
            ])->id;
        }

        $company = $this->makeCompany([
            ...$assets,
            'name' => 'Target Freight',
            'pipeline_company_id' => 1001,
            'requires_brand' => true,
            'brand' => 'TARGET',
            'enable_documents' => true,
            'theme_id' => $theme->id,
        ]);
        $this->makeCompanyApiToken($company);
        $this->makeCompany([
            'name' => 'Plain Freight',
            'pipeline_company_id' => 1001,
            'requires_brand' => true,
            'brand' => 'PLAIN',
            'theme_id' => null,
        ]);

        $this->browse(function (Browser $browser) use ($company, $theme, $width, $height) {
            $browser->resize($width, $height)
                ->visit('/trackShipment?trackingNumber=PRO123&searchOption=carrierPro&companyId=1001&brand=target')
                ->waitForText('Shipment Details')
                ->assertTitleContains('Target Freight')
                ->assertPresent('img[src$="/images/target-banner.png"]')
                ->assertPresent('img[src$="/images/target-logo.png"]')
                ->assertPresent('[data-tracking-footer] img[src$="/images/target-footer.png"]')
                ->assertScript('document.documentElement.style.getPropertyValue("--primary")', $theme->colors['root']['primary'])
                ->assertScript('document.documentElement.scrollWidth === document.documentElement.clientWidth')
                ->assertSee('Shipment Documents')
                ->assertDontSee('Tracking Map')
                ->assertSee('Status History');

            $company->update(['name' => 'Updated Target Freight', 'theme_id' => null]);

            $browser->press('Refresh')
                ->waitUntil('document.title.includes("Updated Target Freight")')
                ->assertScript('document.documentElement.style.getPropertyValue("--primary")', '')
                ->assertPathIs('/trackShipment')
                ->assertQueryStringHas('trackingNumber', 'PRO123')
                ->assertQueryStringHas('searchOption', 'carrierPro')
                ->assertQueryStringHas('companyId', '1001')
                ->assertQueryStringHas('brand', 'target')
                ->assertSee('Shipment Documents')
                ->visit('/trackShipment?trackingNumber=BOL123&searchOption=bol&brand=plain')
                ->waitForText('Shipment Details')
                ->assertTitleContains('Plain Freight')
                ->assertPresent('img[src$="/images/FW_Logo_Full_Color.png"]')
                ->assertNotPresent('img[src*="/images/target-"]')
                ->assertScript('document.documentElement.style.getPropertyValue("--primary")', '')
                ->assertDontSee('Shipment Documents')
                ->assertDontSee('Tracking Map');
        });
    }
}
