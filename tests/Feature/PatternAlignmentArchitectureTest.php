<?php

namespace Tests\Feature;

use App\Http\Middleware\AttachRequestContext;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class PatternAlignmentArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_context_middleware_adds_a_stable_request_id_header(): void
    {
        $this->get(route('login'), ['X-Request-Id' => 'alignment-test-request'])
            ->assertOk()
            ->assertHeader('X-Request-Id', 'alignment-test-request');
    }

    public function test_application_boot_registers_request_context_and_api_json_exceptions(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString(AttachRequestContext::class, $bootstrap);
        $this->assertStringContainsString('shouldRenderJsonWhen', $bootstrap);
        $this->assertStringContainsString('$request->is(\'api/*\')', $bootstrap);
    }

    public function test_frontend_boot_registers_conservative_vue_query_defaults(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('new QueryClient', $appJs);
        $this->assertStringContainsString('staleTime: 60 * 1000', $appJs);
        $this->assertStringContainsString('gcTime: 10 * 60 * 1000', $appJs);
        $this->assertStringContainsString('refetchOnWindowFocus: false', $appJs);
        $this->assertStringContainsString('retry: 1', $appJs);
    }

    public function test_admin_web_routes_keep_explicit_auth_or_permission_boundaries(): void
    {
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! is_string($name) || ! str_starts_with($name, 'admin.')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth', $middleware, "{$name} must require an authenticated session.");

            if (in_array($name, ['admin.dashboard', 'admin.tracking.index'], true)) {
                continue;
            }

            $this->assertTrue(
                collect($middleware)->contains(
                    fn (string $entry): bool => str_starts_with($entry, 'permission:')
                        || $entry === 'App\Http\Middleware\EnsureSuperAdmin'
                        || $entry === EnsureSuperAdmin::class,
                ),
                "{$name} must be guarded by an admin permission or super-admin middleware.",
            );
        }
    }

    public function test_mutating_api_controller_methods_use_form_request_boundaries(): void
    {
        foreach ($this->apiControllers() as $controller) {
            $reflection = new ReflectionClass($controller);

            foreach ($reflection->getMethods() as $method) {
                if (! in_array($method->getName(), ['store', 'update', 'destroy'], true)
                    && ! str_starts_with($method->getName(), 'toggle')
                    && ! str_starts_with($method->getName(), 'set')
                    && ! str_starts_with($method->getName(), 'clear')
                    && ! str_starts_with($method->getName(), 'assign')
                    && ! str_starts_with($method->getName(), 'sync')) {
                    continue;
                }

                $hasFormRequest = collect($method->getParameters())->contains(function ($parameter): bool {
                    $type = $parameter->getType();

                    return $type instanceof ReflectionNamedType
                        && is_subclass_of($type->getName(), FormRequest::class);
                });

                $this->assertTrue(
                    $hasFormRequest,
                    sprintf('%s::%s must receive a FormRequest.', $controller, $method->getName()),
                );
            }
        }
    }

    public function test_sensitive_payload_values_are_not_logged_directly(): void
    {
        foreach ($this->applicationFiles() as $file) {
            $contents = file_get_contents($file);

            if (! str_contains($contents, 'Log::') && ! str_contains($contents, 'logger(')) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/Log::(?:debug|info|notice|warning|error|critical|alert|emergency)\([^;]*(api_token|trackingData|shipmentDocumentsResponse->json|shipmentCoordinates|document_payload|raw_payload)/s',
                $contents,
                "{$file} logs sensitive payload or credential content directly.",
            );
        }
    }

    /**
     * @return array<int, class-string>
     */
    private function apiControllers(): array
    {
        return collect(glob(app_path('Http/Controllers/Api/*.php')) ?: [])
            ->map(fn (string $path): string => 'App\\Http\\Controllers\\Api\\'.basename($path, '.php'))
            ->filter(fn (string $class): bool => class_exists($class))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function applicationFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
