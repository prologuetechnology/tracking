<?php

namespace Tests\Unit;

use App\Models\CompanyFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyModelBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_legacy_feature_columns_and_feature_relationships(): void
    {
        $company = $this->makeCompany([
            'enable_map' => false,
            'enable_documents' => false,
        ]);

        $company->syncFeatures(['enable_map', 'enable_documents']);
        $company->syncLegacyFeatureColumns(['enable_map', 'enable_documents']);

        $this->assertTrue($company->fresh()->enable_map);
        $this->assertTrue($company->fresh()->enable_documents);

        $company->disableFeature('enable_map');
        $company->syncLegacyFeatureColumn('enable_map', false);

        $this->assertFalse($company->fresh()->enable_map);
        $this->assertFalse($company->fresh()->hasFeature('enable_map'));
        $this->assertTrue($company->fresh()->hasFeature('enable_documents'));
    }

    public function test_it_resolves_companies_by_brand_company_id_and_pipeline_company_id(): void
    {
        $company = $this->makeCompany([
            'pipeline_company_id' => 9988,
            'brand' => 'ACME',
            'requires_brand' => true,
        ]);

        $this->assertSame($company->id, $company::findByIdentifier(null, 9988)?->id);
        $this->assertSame($company->id, $company::findByIdentifier(null, null, 9988)?->id);
        $this->assertNull($company::findByIdentifier(null, null, 9999));
    }
}
