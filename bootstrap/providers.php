<?php

return array_values(array_filter([
    App\Providers\AppServiceProvider::class,
    \SocialiteProviders\Manager\ServiceProvider::class,
    in_array(env('APP_ENV'), ['local', 'testing', 'dusk.local'], true)
        ? App\Providers\DuskServiceProvider::class
        : null,
]));
