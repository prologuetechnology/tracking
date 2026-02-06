<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\DetailedTrackingController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Models\AllowedDomain;
use App\Models\Company;
use App\Models\CompanyFeature;
use App\Models\Image;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

Route::get('/', function () {
    if (Auth::check() && ! Auth::user()->can('company:show')) {
        return redirect(route('admin.tracking.index'));
    }

    if (Auth::check() && Auth::user()->can('company:show')) {
        return redirect(route('admin.companies.index'));
    }

    return redirect(route('login'));
})->name('home');

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect(route('admin.dashboard'));
    }

    return Inertia::render('auth/Login');
})->name('login');

// Admin routes
Route::prefix('admin')
    ->as('admin.')
    ->middleware(['auth'])
    ->group(function () {
        // Company routes
        // Company index
        Route::get('/companies', function () {
            if (Auth::user()->cannot('company:show')) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view companies.');
            }

            $companies = Company::with(['logo', 'theme', 'features'])->get();

            return Inertia::render('admin/companies/Index', [
                'initialCompanies' => $companies,
            ]);
        })->name('companies.index');

        // Company create
        Route::get('/companies/create', function () {
            if (Auth::user()->cannot('company:store')) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have permission to create companies.');
            }

            return Inertia::render('admin/companies/Create');
        })->name('companies.create');

        // Company show
        Route::get('/company/{company:uuid}', function (Company $company) {
            if (Auth::user()->cannot('company:show')) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view companies.');
            }

            $companyFeatures = CompanyFeature::query()
                ->orderBy('id', 'desc')
                ->get();

            $company->load(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

            return Inertia::render('admin/companies/Edit', [
                'companyInitialValues' => $company,
                'companyFeaturesInitialValues' => $companyFeatures,
            ]);
        })->name('companies.show');

        // Theme routes
        // Theme index
        Route::get('themes', function () {
            if (Auth::user()->cannot('theme:show')) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view themes.');
            }

            $themes = Theme::all();

            return Inertia::render('admin/themes/Index', [
                'initialThemes' => $themes,
            ]);
        })->name('themes.index');

        // Theme create
        Route::get('themes/create', function (Theme $theme) {
            if (Auth::user()->cannot('theme:store')) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have permission to create themes.');
            }

            return Inertia::render('admin/themes/Create');
        })->name('themes.create');

        // Theme show
        Route::get('themes/{theme:uuid}', function (Theme $theme) {
            if (Auth::user()->cannot('theme:show')) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view themes.');
            }

            return Inertia::render('admin/themes/Edit', [
                'initialTheme' => $theme,
            ]);
        })->name('themes.show');

        // Images routes

        // Images index
        Route::get('images', function () {
            if (Auth::user()->cannot('image:show')) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view images.');
            }

            $images = Image::with('imageType')->get();

            return Inertia::render('admin/image/Index', [
                'initialImages' => $images,
            ]);
        })->name('image.index');

        // Tracking routes

        // Tracking index
        Route::get('tracking', function () {
            return Inertia::render('admin/tracking/Index');
        })->name('tracking.index');

        // Admin Routes for Roles and Permissions
        Route::middleware(EnsureSuperAdmin::class)
            ->group(function () {
                // Allowed Domains routes
                Route::get('allowed-domains', function () {
                    return Inertia::render('admin/allowedDomains/Index', [
                        'initialAllowedDomains' => AllowedDomain::all(),
                    ]);
                })->name('allowed-domains.index');

                // Users routes
                Route::get('users', function () {
                    $users = User::with('roles')->get();

                    return Inertia::render('admin/users/Index', [
                        'initialUsers' => $users,
                        'allRoles' => Role::all()->map(fn ($role) => [
                            'id' => $role->id,
                            'name' => $role->name,
                        ]),
                    ]);
                })->name('users.index');

                // Permissions routes
                Route::get('permissions', function () {
                    return Inertia::render('admin/permissions/Index', [
                        'initialPermissions' => Permission::all(),
                    ]);
                })->name('permissions.index');

                Route::get('/permissions/{permission}', function (Permission $permission) {
                    return Inertia::render('admin/permissions/Edit', [
                        'initialPermission' => $permission,
                    ]);
                })->name('permissions.show');

                Route::get('role', function () {
                    return Inertia::render('admin/role/Index', [
                        'roles' => Role::with('permissions')->get()->map(fn ($role) => [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permissions' => $role->permissions->pluck('name'),
                        ]),
                    ]);
                })->name('role.index');

                Route::get('role/show/{role}', function (Role $role) {
                    $role->load('permissions');

                    return Inertia::render('admin/role/Edit', [
                        'initialRole' => $role,
                        'allPermissions' => \Spatie\Permission\Models\Permission::all()->map(fn ($permission) => [
                            'id' => $permission->id,
                            'name' => $permission->name,
                        ]),
                    ]);
                })->name('role.show');
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
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'dusk@example.com'],
        ['first_name' => 'Dusk', 'last_name' => 'Tester', 'password' => bcrypt('password')]
    );
    Auth::login($user);

    return redirect(route('home'));
});

require __DIR__.'/auth.php';
