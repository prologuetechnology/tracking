<?php

namespace Tests\Unit;

use App\Actions\CompanyApiTokens\CreateCompanyApiToken;
use App\Actions\CompanyApiTokens\DeleteCompanyApiToken;
use App\Actions\CompanyApiTokens\ValidateCompanyApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class CompanyApiTokenActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.pipeline.api_url', 'https://pipeline.example/api');
    }

    public function test_it_creates_and_validates_company_api_tokens_against_pipeline_company_id(): void
    {
        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response([
                'data' => [['companyId' => 3210]],
            ], 200),
        ]);

        $company = $this->makeCompany(['pipeline_company_id' => 3210]);

        $created = (new CreateCompanyApiToken)->execute($company, [
            'api_token' => 'company-token',
            'trackingNumber' => 'BOL123',
        ]);

        $validated = (new ValidateCompanyApiToken)->execute($company->fresh());

        $this->assertSame($company->id, $created->company_id);
        $this->assertEquals(1, $validated->is_valid);
    }

    public function test_it_rejects_duplicate_and_invalid_company_api_tokens(): void
    {
        $company = $this->makeCompany(['pipeline_company_id' => 3210]);
        $this->makeCompanyApiToken($company);

        $this->expectException(ConflictHttpException::class);
        (new CreateCompanyApiToken)->execute($company->fresh(), [
            'api_token' => 'duplicate-token',
            'trackingNumber' => 'BOL123',
        ]);
    }

    public function test_it_throws_for_invalid_or_missing_company_api_tokens_and_can_delete_them(): void
    {
        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response([
                'data' => [['companyId' => 9999]],
            ], 200),
        ]);

        $company = $this->makeCompany(['pipeline_company_id' => 3210]);

        try {
            (new CreateCompanyApiToken)->execute($company, [
                'api_token' => 'bad-token',
                'trackingNumber' => 'BOL123',
            ]);
            $this->fail('Expected invalid token exception.');
        } catch (UnauthorizedHttpException) {
            $this->assertDatabaseCount('company_api_tokens', 0);
        }

        try {
            (new ValidateCompanyApiToken)->execute($company->fresh());
            $this->fail('Expected missing token exception.');
        } catch (NotFoundHttpException) {
            $this->assertTrue(true);
        }

        $token = $this->makeCompanyApiToken($company);
        (new DeleteCompanyApiToken)->execute($token);

        $this->assertDatabaseMissing('company_api_tokens', ['id' => $token->id]);
    }
}
