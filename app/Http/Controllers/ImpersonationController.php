<?php

namespace App\Http\Controllers;

use App\Actions\Impersonation\StartImpersonation;
use App\Actions\Impersonation\StopImpersonation;
use App\Http\Requests\ImpersonateUserRequest;
use App\Http\Requests\StopImpersonationRequest;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly StartImpersonation $startImpersonation,
        private readonly StopImpersonation $stopImpersonation,
    ) {
    }

    public function impersonate(ImpersonateUserRequest $request, int $userId)
    {
        $userToImpersonate = $this->startImpersonation->execute($request, $userId);

        return redirect(route('home'))
            ->with('status', 'You are now impersonating user ID '.$userToImpersonate->id.'.');
    }

    public function stopImpersonate(StopImpersonationRequest $request)
    {
        $originalUser = $this->stopImpersonation->execute($request);

        if ($originalUser instanceof User) {
            return redirect(route('admin.users.index'))
                ->with('status', 'You have returned to your account.')
                ->with('reload', true);
        }

        return redirect(route('home'))
            ->with('status', 'Impersonation ended. Please log in.');
    }
}
