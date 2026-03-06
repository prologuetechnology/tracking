<?php

namespace App\Actions\Impersonation;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StartImpersonation
{
    public function execute(Request $request, int $userId): User
    {
        $userToImpersonate = User::query()->find($userId);

        if ($userToImpersonate === null) {
            throw new NotFoundHttpException();
        }

        if ($userToImpersonate->hasRole('Super Admin')) {
            throw new AccessDeniedHttpException('Cannot impersonate a super admin.');
        }

        $request->session()->put('impersonate_original_id', Auth::id());

        Auth::guard('web')->login($userToImpersonate);

        return $userToImpersonate;
    }
}
