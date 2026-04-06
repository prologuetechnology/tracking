<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserCanImpersonate;
use App\Http\Middleware\EnsureUserCanReadCompany;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Resources\AllowedDomainResource;
use App\Http\Resources\CompanyFeatureResource;
use App\Http\Resources\ImageTypeResource;
use App\Http\Resources\ShipmentCoordinateResource;
use App\Http\Resources\ShipmentDocumentResource;
use App\Http\Resources\ThemeResource;
use App\Models\CompanyFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MiddlewareAndAdditionalResourceContractsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures(withImageTypes: true);
    }

    public function test_super_admin_middleware_rejects_non_super_admin_users_and_allows_super_admins(): void
    {
        $middleware = new EnsureSuperAdmin;

        try {
            $middleware->handle($this->requestForUser($this->makeStandardUser()), $this->nextResponse(...));
            $this->fail('Expected the middleware to reject non-super-admin users.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $response = $middleware->handle($this->requestForUser($this->makeSuperAdmin()), $this->nextResponse(...));

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_impersonation_and_company_read_middlewares_enforce_their_permissions(): void
    {
        $impersonateMiddleware = new EnsureUserCanImpersonate;
        $companyReadMiddleware = new EnsureUserCanReadCompany;

        try {
            $impersonateMiddleware->handle($this->requestForUser($this->makeStandardUser()), $this->nextResponse(...));
            $this->fail('Expected impersonation middleware to reject users without the permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        try {
            $companyReadMiddleware->handle($this->requestForUser($this->makeStandardUser()), $this->nextResponse(...));
            $this->fail('Expected company read middleware to reject users without the permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $impersonator = $this->makeUserWithPermission('user:impersonate');
        $companyReader = $this->makeUserWithPermission('company:show');

        $this->assertSame(
            204,
            $impersonateMiddleware
                ->handle($this->requestForUser($impersonator), $this->nextResponse(...))
                ->getStatusCode(),
        );

        $this->assertSame(
            204,
            $companyReadMiddleware
                ->handle($this->requestForUser($companyReader), $this->nextResponse(...))
                ->getStatusCode(),
        );
    }

    public function test_handle_inertia_requests_shares_auth_app_and_impersonation_context(): void
    {
        config()->set('app.name', 'Tracking');
        config()->set('app.url', 'https://tracking.example');
        config()->set('filesystems.disks.spaces.region', 'us-east-1');
        config()->set('filesystems.disks.spaces.bucket', 'tracking-bucket');

        $user = $this->makeSuperAdmin(['email' => 'shared-props@example.test']);
        $request = Request::create('https://tracking.example/admin/users', 'GET');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put('impersonate_original_id', 123);
        $request->setUserResolver(fn () => $user);

        $shared = (new HandleInertiaRequests)->share($request);
        $ziggy = $shared['ziggy'];

        $this->assertSame('shared-props@example.test', $shared['auth']['user']['email']);
        $this->assertSame(['Super Admin'], $shared['auth']['roles']);
        $this->assertContains('company:show', $shared['auth']['permissions']);
        $this->assertTrue($shared['is_impersonating']);
        $this->assertSame('Tracking', $shared['app']['name']);
        $this->assertSame('https://tracking.example', $shared['app']['appUrl']);
        $this->assertSame('tracking-bucket', $shared['app']['disks']['spaces']['bucket']);
        $this->assertSame('https://tracking.example/admin/users', $ziggy()['location']);
    }

    public function test_remaining_resources_normalize_booleans_dates_and_nested_payloads(): void
    {
        $user = $this->makeSuperAdmin();
        $allowedDomain = $this->makeAllowedDomain($user, ['domain' => 'example.com', 'is_active' => 1]);
        $feature = CompanyFeature::query()->where('slug', 'enable_map')->firstOrFail();
        $theme = $this->makeTheme(['name' => 'Resource Theme', 'is_system' => 1]);
        $imageType = $this->findImageTypeFixture('logo');

        $allowedDomainResource = AllowedDomainResource::make($allowedDomain)->toArray(Request::create('/'));
        $featureResource = CompanyFeatureResource::make($feature)->toArray(Request::create('/'));
        $themeResource = ThemeResource::make($theme)->toArray(Request::create('/'));
        $imageTypeResource = ImageTypeResource::make($imageType)->toArray(Request::create('/'));
        $coordinateResource = ShipmentCoordinateResource::make([
            'lastLocation' => (object) [
                'coordinates' => (object) ['lat' => 41.88, 'lng' => -90.5],
            ],
        ])->resolve();
        $documentResource = ShipmentDocumentResource::make([
            'name' => 'bol',
            'url' => 'https://tracking.example/documents/bol.pdf',
            'type' => 'application/pdf',
            'size' => 1024,
            'last_modified' => 'Wed, 06 Mar 2024 12:00:00 GMT',
        ])->resolve();

        $this->assertTrue($allowedDomainResource['is_active']);
        $this->assertSame('example.com', $allowedDomainResource['domain']);
        $this->assertSame($user->id, $allowedDomainResource['created_by']);
        $this->assertFalse($featureResource['default_enabled']);
        $this->assertSame('enable_map', $featureResource['slug']);
        $this->assertTrue($themeResource['is_system']);
        $this->assertSame('Resource Theme', $themeResource['name']);
        $this->assertSame('logo', $imageTypeResource['name']);
        $this->assertSame(41.88, $coordinateResource['lastLocation']['coordinates']['lat']);
        $this->assertFalse($documentResource['error']);
        $this->assertSame('application/pdf', $documentResource['type']);
    }

    private function requestForUser(mixed $user): Request
    {
        $request = Request::create('/middleware-test', 'GET');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function nextResponse(Request $request): Response
    {
        return response()->noContent();
    }
}
