<?php

namespace Tests\Unit;

use App\Actions\Impersonation\StartImpersonation;
use App\Actions\Impersonation\StopImpersonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ImpersonationActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures();
    }

    public function test_it_starts_and_stops_impersonation_when_the_target_is_allowed(): void
    {
        $admin = $this->makeSuperAdmin();
        $target = $this->makeStandardUser();

        Auth::login($admin);

        $request = Request::create('/impersonate', 'POST');
        $request->setLaravelSession(app('session')->driver());

        $impersonated = (new StartImpersonation)->execute($request, $target->id);

        $this->assertSame($target->id, $impersonated->id);
        $this->assertSame($admin->id, $request->session()->get('impersonate_original_id'));
        $this->assertSame($target->id, Auth::id());

        $restored = (new StopImpersonation)->execute($request);

        $this->assertSame($admin->id, $restored?->id);
        $this->assertSame($admin->id, Auth::id());
    }

    public function test_it_rejects_missing_or_super_admin_impersonation_targets(): void
    {
        $admin = $this->makeSuperAdmin(['email' => 'impersonator@example.com']);
        $otherSuperAdmin = $this->makeSuperAdmin(['email' => 'target@example.com']);

        Auth::login($admin);

        $request = Request::create('/impersonate', 'POST');
        $request->setLaravelSession(app('session')->driver());

        try {
            (new StartImpersonation)->execute($request, 999_999);
            $this->fail('Expected missing impersonation target exception.');
        } catch (NotFoundHttpException) {
            $this->assertTrue(true);
        }

        $this->expectException(AccessDeniedHttpException::class);
        (new StartImpersonation)->execute($request, $otherSuperAdmin->id);
    }
}
