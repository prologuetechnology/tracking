<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Azure\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('testing', 'dusk.local')) {
            // Test company IDs can overlap Herd's IDs. Deleting a test company must only remove its test proofs.
            config(['filesystems.disks.local.root' => storage_path('framework/testing/'.$this->app->environment().'/private')]);
        }

        if ($this->app->environment('dusk.local')) {
            // Browser tests use the built assets even when Herd is serving Vite on another origin.
            Vite::useHotFile(storage_path('framework/dusk.hot'));
        }

        Vite::prefetch(concurrency: 3);

        RateLimiter::for('tracking-studio', function (Request $request) {
            $cached = $request->filled('snapshot_token');

            return Limit::perMinute($cached ? 180 : 30)
                ->by($request->user()->id.($cached ? ':render' : ':lookup'));
        });

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('azure', Provider::class);
        });
    }
}
