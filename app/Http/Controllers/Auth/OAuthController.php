<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\AllowedDomain;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response as HttpCodes;

class OAuthController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $socialiteUser = Socialite::driver($provider)->user();

        $emailDomain = substr(strrchr($socialiteUser->getEmail(), '@'), 1);

        $allowedDomains = AllowedDomain::query()
            ->where('is_active', true)
            ->pluck('domain')
            ->toArray();

        // Check if the email domain is allowed
        if (! in_array($emailDomain, $allowedDomains)) {
            return redirect()->route('login')->withErrors([
                'email' => 'You must be an allowed user to login.',
            ]);
        }

        $userModel = new User;

        $user = $userModel->updateOrCreate([
            'azure_id' => $socialiteUser->id,
        ], [
            'first_name' => $socialiteUser->user['givenName'],
            'last_name' => $socialiteUser->user['surname'],
            'email' => $socialiteUser->email,
            'password' => bcrypt(Str::random(32)),
            'azure_token' => $socialiteUser->token,
            'azure_refresh_token' => $socialiteUser->refreshToken,
            'avatar_url' => $socialiteUser->avatar,
        ]);

        if ($user->roles->isEmpty()) {
            $user->syncRoles([
                Role::findOrCreate(RoleEnum::STANDARD->value)->name,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::guard()->logout();
        $request->session()->flush();

        return response()->json([], HttpCodes::HTTP_NO_CONTENT);
    }
}
