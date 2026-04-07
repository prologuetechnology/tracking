<?php

use App\Providers\AppServiceProvider;
use App\Providers\DuskServiceProvider;
use SocialiteProviders\Manager\ServiceProvider;

return array_values(array_filter([
    AppServiceProvider::class,
    ServiceProvider::class,
    in_array(env('APP_ENV'), ['local', 'testing', 'dusk.local'], true)
        ? DuskServiceProvider::class
        : null,
]));
