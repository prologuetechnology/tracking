<?php

namespace App\Actions\Impersonation;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StopImpersonation
{
    public function execute(Request $request): ?User
    {
        $originalId = $request->session()->pull('impersonate_original_id');

        if (! $originalId) {
            Auth::guard('web')->logout();

            return null;
        }

        $originalUser = User::query()->find($originalId);

        if ($originalUser === null) {
            Auth::guard('web')->logout();

            return null;
        }

        Auth::guard('web')->login($originalUser);

        return $originalUser;
    }
}
