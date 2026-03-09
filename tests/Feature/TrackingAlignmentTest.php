<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyApiToken;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrackingAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config()->set('services.pipeline.api_url', 'https://pipeline.example/api');
        config()->set('services.pipeline.base_url', 'https://pipeline.example');
        config()->set('services.pipeline.api_key', 'pipeline-key');
    }

    public function test_tracking_statuses_return_a_normalized_payload(): void
    {
        Http::preventStrayRequests();

        $company = $this->createCompany(enableMap: true, enableDocuments: true);
        CompanyApiToken::query()->create([
            'company_id' => $company->id,
            'api_token' => 'company-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        $this->fakeTrackingApis();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.shipmentTracking'), [
                'trackingNumber' => 'BOL123',
                'searchOption' => 'bol',
            ])
            ->assertOk()
            ->assertJsonPath('trackingData.bolNum', 'BOL123')
            ->assertJsonPath('company.name', $company->name)
            ->assertJsonPath('shipmentCoordinates.0.lastLocation.coordinates.lng', -90.5)
            ->assertJsonPath('shipmentDocuments.0.name', 'bol')
            ->assertJsonMissingPath('trackingData.data');
    }

    public function test_tracking_statuses_return_not_found_when_search_returns_no_rows(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.shipmentTracking'), [
                'trackingNumber' => 'BOL404',
                'searchOption' => 'bol',
            ])
            ->assertNotFound();
    }

    public function test_shipment_documents_endpoint_requires_company_show_permission(): void
    {
        $company = $this->createCompany();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.shipmentDocuments'), [
                'trackingNumber' => 'BOL123',
                'companyId' => $company->pipeline_company_id,
            ])
            ->assertForbidden();
    }

    public function test_shipment_documents_endpoint_returns_an_empty_array_when_documents_are_disabled(): void
    {
        $company = $this->createCompany(enableDocuments: false);
        $user = $this->createUserWithPermission('company:show');

        $this->actingAs($user)
            ->postJson(route('api.shipmentDocuments'), [
                'trackingNumber' => 'BOL123',
                'companyId' => $company->pipeline_company_id,
            ])
            ->assertOk()
            ->assertExactJson([
                'shipmentDocuments' => [],
            ]);
    }

    public function test_shipment_coordinates_endpoint_is_forbidden_when_map_is_disabled(): void
    {
        $company = $this->createCompany(enableMap: false);

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.shipmentCoordinates'), [
                'trackingNumber' => 'BOL123',
                'pipelineCompanyId' => (string) $company->pipeline_company_id,
            ])
            ->assertForbidden();
    }

    public function test_document_metadata_head_failures_are_normalized(): void
    {
        Http::preventStrayRequests();

        $company = $this->createCompany(enableDocuments: true);
        CompanyApiToken::query()->create([
            'company_id' => $company->id,
            'api_token' => 'company-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        Http::fake([
            'https://pipeline.example/api/Execute/GetDocuments' => Http::response([
                [
                    'name' => 'bol',
                    'file' => 'http://documents.example/bol.pdf',
                ],
            ], 200),
            'https://documents.example/*' => function () {
                throw new ConnectionException('Document HEAD failed.');
            },
        ]);

        $user = $this->createUserWithPermission('company:show');

        $this->actingAs($user)
            ->postJson(route('api.shipmentDocuments'), [
                'trackingNumber' => 'BOL123',
                'companyId' => $company->pipeline_company_id,
            ])
            ->assertOk()
            ->assertJsonPath('shipmentDocuments.0.url', 'https://documents.example/bol.pdf')
            ->assertJsonPath('shipmentDocuments.0.type', null)
            ->assertJsonPath('shipmentDocuments.0.error', true);
    }

    public function test_public_tracking_page_is_hydrated_with_normalized_initial_props(): void
    {
        Http::preventStrayRequests();

        $company = $this->createCompany(enableMap: true, enableDocuments: true);
        CompanyApiToken::query()->create([
            'company_id' => $company->id,
            'api_token' => 'company-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        $this->fakeTrackingApis();

        $this->get(route('trackShipment.index', [
            'trackingNumber' => 'BOL123',
            'searchOption' => 'bol',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('brandedTracking/Index')
                ->where('initialTrackingData.bolNum', 'BOL123')
                ->where('initialCompany.name', $company->name)
                ->has('initialShipmentCoordinates', 1)
                ->where('initialShipmentDocuments.0.name', 'bol'));
    }

    public function test_public_tracking_redirects_to_not_found_when_brand_is_required_but_missing(): void
    {
        Http::preventStrayRequests();

        $company = $this->createCompany(requiresBrand: true, brand: 'ACME');
        CompanyApiToken::query()->create([
            'company_id' => $company->id,
            'api_token' => 'company-token',
            'bol' => 'BOL123',
            'is_valid' => true,
        ]);

        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response($this->shipmentSearchPayload(
                companyId: $company->pipeline_company_id,
                bolNumber: 'BOL123',
            ), 200),
        ]);

        $this->get(route('trackShipment.index', [
            'trackingNumber' => 'BOL123',
            'searchOption' => 'bol',
        ]))
            ->assertRedirect(route('trackShipment.notFound', 'BOL123'));
    }

    protected function fakeTrackingApis(?int $companyId = 1001, string $bolNumber = 'BOL123'): void
    {
        Http::fake([
            'https://pipeline.example/api/shipmentSearch' => Http::response(
                $this->shipmentSearchPayload($companyId, $bolNumber),
                200,
            ),
            'https://pipeline.example/app.php?r=mapApi/getRoutes*' => Http::response([
                [
                    'lastLocation' => [
                        'coordinates' => [
                            'lat' => 41.88,
                            'lng' => -90.5,
                        ],
                    ],
                    'allKnownLocations' => [
                        [
                            'coordinates' => [
                                'lat' => 41.88,
                                'lng' => -90.5,
                            ],
                        ],
                        [
                            'coordinates' => [
                                'lat' => 41.9,
                                'lng' => -90.45,
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://pipeline.example/api/Execute/GetDocuments' => Http::response([
                [
                    'name' => 'bol',
                    'file' => 'http://documents.example/bol.pdf',
                ],
            ], 200),
            'https://documents.example/*' => Http::response('', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => '1024',
                'Last-Modified' => 'Wed, 06 Mar 2024 12:00:00 GMT',
            ]),
        ]);
    }

    protected function shipmentSearchPayload(?int $companyId = 1001, string $bolNumber = 'BOL123'): array
    {
        return [
            'data' => [
                [
                    'id' => 1,
                    'companyId' => $companyId,
                    'bolNum' => $bolNumber,
                    'carrierPro' => 'PRO123',
                    'carrierName' => 'Acme Carrier',
                    'allStatuses' => [
                        [
                            'id' => 10,
                            'current_status' => 'Delivered',
                            'status_date_time' => '2026-03-06T10:00:00Z',
                            'current_location' => 'Chicago, IL',
                            'pro_number' => 'PRO123',
                        ],
                    ],
                    'lineItems' => [
                        ['pieces' => 2],
                    ],
                    'totalWeight' => 1200,
                    'originLocation' => [
                        'name' => 'Origin Hub',
                        'address' => '1 Start St',
                        'city' => 'Dallas',
                        'state' => 'TX',
                        'zipCode' => '75001',
                    ],
                    'destinationLocation' => [
                        'name' => 'Destination Hub',
                        'address' => '9 End Ave',
                        'city' => 'Chicago',
                        'state' => 'IL',
                        'zipCode' => '60601',
                    ],
                    'estimatedDeliveryDate' => '2026-03-06',
                    'estimatedPickupDate' => '2026-03-01',
                    'specialInstructions' => 'Leave at dock door 3.',
                    'poNumbers' => ['PO-1'],
                ],
            ],
        ];
    }

    protected function createUserWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }

    protected function createCompany(
        bool $enableMap = false,
        bool $enableDocuments = false,
        bool $requiresBrand = false,
        ?string $brand = null,
    ): Company {
        $theme = Theme::query()->create([
            'name' => 'Tracking Theme '.str()->uuid(),
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
            'name' => 'Acme Logistics',
            'website' => 'https://acme.test',
            'phone' => '555-000-1111',
            'email' => 'dispatch@acme.test',
            'pipeline_company_id' => 1001,
            'theme_id' => $theme->id,
            'enable_map' => $enableMap,
            'enable_documents' => $enableDocuments,
            'is_active' => true,
            'requires_brand' => $requiresBrand,
            'brand' => $brand,
        ]);
    }
}
