<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing(['roles.permissions', 'permissions']);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? UserResource::make($user)->resolve() : null,
                'roles' => $user?->getRoleNames()->values()->all() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->values()->all() ?? [],
            ],
            'is_impersonating' => $request->session()->has('impersonate_original_id'),
            'app' => [
                'env' => App::environment(),
                'name' => config('app.name'),
                'appUrl' => config('app.url'),
                'disks' => [
                    'spaces' => [
                        'region' => config('filesystems.disks.spaces.region'),
                        'bucket' => config('filesystems.disks.spaces.bucket'),
                    ],
                ],
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ];
    }
}
