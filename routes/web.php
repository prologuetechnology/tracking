<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\DetailedTrackingController;
use App\Http\Controllers\Pages\Admin\AllowedDomainPageController;
use App\Http\Controllers\Pages\Admin\CompanyPageController;
use App\Http\Controllers\Pages\Admin\DashboardController;
use App\Http\Controllers\Pages\Admin\ImagePageController;
use App\Http\Controllers\Pages\Admin\PermissionPageController;
use App\Http\Controllers\Pages\Admin\RolePageController;
use App\Http\Controllers\Pages\Admin\ThemePageController;
use App\Http\Controllers\Pages\Admin\TrackingPageController;
use App\Http\Controllers\Pages\Admin\UserPageController;
use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\LoginPageController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

Route::get('/', HomeController::class)->name('home');
Route::get('/login', LoginPageController::class)->name('login');

Route::prefix('admin')
    ->as('admin.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::controller(CompanyPageController::class)
            ->group(function () {
                Route::get('/companies', 'index')
                    ->middleware('permission:company:show')
                    ->name('companies.index');
                Route::get('/companies/create', 'create')
                    ->middleware('permission:company:store')
                    ->name('companies.create');
                Route::get('/company/{company:uuid}', 'show')
                    ->middleware('permission:company:show')
                    ->name('company.show');
                Route::get('/companies/{company:uuid}', 'show')
                    ->middleware('permission:company:show')
                    ->name('companies.show');
            });

        Route::controller(ThemePageController::class)
            ->group(function () {
                Route::get('themes', 'index')
                    ->middleware('permission:theme:show')
                    ->name('themes.index');
                Route::get('themes/create', 'create')
                    ->middleware('permission:theme:store')
                    ->name('themes.create');
                Route::get('theme/{theme:uuid}', 'show')
                    ->middleware('permission:theme:show')
                    ->name('theme.show');
                Route::get('themes/{theme:uuid}', 'show')
                    ->middleware('permission:theme:show')
                    ->name('themes.show');
            });

        Route::get('images', [ImagePageController::class, 'index'])
            ->middleware('permission:image:show')
            ->name('image.index');

        Route::get('tracking', TrackingPageController::class)
            ->name('tracking.index');

        Route::middleware(EnsureSuperAdmin::class)
            ->group(function () {
                Route::get('allowed-domains', [AllowedDomainPageController::class, 'index'])
                    ->name('allowed-domains.index');
                Route::get('users', [UserPageController::class, 'index'])
                    ->name('users.index');
                Route::get('permissions', [PermissionPageController::class, 'index'])
                    ->name('permissions.index');
                Route::get('permissions/create', [PermissionPageController::class, 'create'])
                    ->name('permissions.create');
                Route::get('/permissions/{permission}', [PermissionPageController::class, 'show'])
                    ->name('permissions.show');
                Route::get('role', [RolePageController::class, 'index'])
                    ->name('role.index');
                Route::get('role/show/{role}', [RolePageController::class, 'show'])
                    ->name('role.show');
            });
    });

Route::get('/trackShipment', [DetailedTrackingController::class, 'index'])->name('trackShipment.index');
Route::get('/trackShipment/notFound/{trackingNumber}', [DetailedTrackingController::class, 'trackingDataNotFound'])->name('trackShipment.notFound');

Route::prefix('oauth')
    ->as('oauth.')
    ->group(function () {
        Route::get('/{provider}/redirect', [OAuthController::class, 'redirect'])->name('redirect');
        Route::get('/{provider}/callback', [OAuthController::class, 'callback'])->name('callback');
        Route::post('/logout', [OAuthController::class, 'logout'])->name('logout');
    });

// Dusk testing route
Route::get('/testing/oauth-login', function () {
    abort_unless(App::environment(['local', 'testing', 'dusk.local']), 404);

    foreach (['company:show', 'permission:show'] as $permission) {
        Permission::findOrCreate($permission);
    }

    $superAdmin = Role::findOrCreate('Super Admin');
    $superAdmin->syncPermissions(Permission::all());

    $user = User::firstOrCreate(
        ['email' => 'dusk@example.com'],
        ['first_name' => 'Dusk', 'last_name' => 'Tester', 'password' => bcrypt('password')]
    );
    $user->syncRoles([$superAdmin->name]);

    Auth::login($user);

    return redirect(route('home'));
});

require __DIR__.'/auth.php';
