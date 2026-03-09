<?php

namespace Tests\Unit;

use App\Http\Resources\CompanyApiTokenResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ImageResource;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\ShipmentTrackingResource;
use App\Http\Resources\TrackingPayloadResource;
use App\Http\Resources\UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResourceNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures(withImageTypes: true);
    }

    public function test_company_and_image_resources_flatten_loaded_relationships(): void
    {
        $logo = $this->makeImage();
        $otherCompany = $this->makeCompany();
        $company = $this->makeCompany(['logo_image_id' => $logo->id])->load(['logo', 'theme', 'features']);
        $otherCompany->forceFill(['logo_image_id' => $logo->id])->save();
        $token = $this->makeCompanyApiToken($company);

        $resource = CompanyResource::make($company->load('apiToken'))->resolve();
        $image = ImageResource::make($logo->loadCount([
            'logoCompanies',
            'bannerCompanies',
            'footerCompanies',
        ]))->resolve();
        $tokenResource = CompanyApiTokenResource::make($token)->resolve();

        $this->assertIsArray($resource['logo']);
        $this->assertIsArray($resource['theme']);
        $this->assertIsArray($resource['api_token']);
        $this->assertIsArray($image['image_type']);
        $this->assertSame(2, $image['company_usage_count']);
        $this->assertTrue($image['is_in_use']);
        $this->assertTrue($tokenResource['is_valid']);
    }

    public function test_tracking_and_rbac_resources_normalize_nested_data(): void
    {
        $permission = Permission::findByName('company:show');
        $role = Role::findByName('Company Admin');
        $user = $this->makeCompanyAdmin()->load(['roles.permissions', 'permissions']);

        $tracking = ShipmentTrackingResource::make([
            'status' => (object) ['label' => 'Delivered'],
        ])->resolve();

        $payload = TrackingPayloadResource::make([
            'trackingData' => ['bolNum' => 'BOL123'],
            'company' => $this->makeCompany(),
            'shipmentCoordinates' => [['lastLocation' => ['coordinates' => ['lat' => 1, 'lng' => 2]]]],
            'shipmentDocuments' => [['name' => 'bol', 'url' => 'https://example.test/bol.pdf']],
        ])->resolve();

        $roleResource = RoleResource::make($role->load('permissions'))->toArray(Request::create('/'));
        $userResource = UserResource::make($user)->toArray(Request::create('/'));
        $permissionResource = PermissionResource::make($permission)->toArray(Request::create('/'));

        $this->assertSame('Delivered', $tracking['status']['label']);
        $this->assertSame('BOL123', $payload['trackingData']['bolNum']);
        $this->assertIsArray($roleResource['permissions']);
        $this->assertIsArray($userResource['roles']);
        $this->assertSame('company:show', $permissionResource['name']);
    }
}
