<?php

use App\Http\Controllers\Api\AllowedDomainController;
use App\Http\Controllers\Api\CompanyApiTokenController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyFeatureController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ImagesController;
use App\Http\Controllers\Api\ImageTypesController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ThemeController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserCanImpersonate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; // Add this line at the top

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::as('api.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::post('shipmentTracking', [TrackingController::class, 'trackingStatuses'])->name('shipmentTracking');
        Route::post('shipmentDocuments', [DocumentController::class, 'shipmentDocuments'])->name('shipmentDocuments');
        Route::post('shipmentCoordinates', [TrackingController::class, 'shipmentCoordinates'])->name('shipmentCoordinates');

        Route::get('companies/features', [CompanyFeatureController::class, 'index'])
            ->name('companies.features.index');
        Route::get('companies/{company}/features', [CompanyFeatureController::class, 'show'])
            ->name('companies.features.show');
        Route::put('companies/{company}/features', [CompanyFeatureController::class, 'sync'])
            ->name('companies.features.sync');
        Route::patch('companies/{company}/features/{feature:slug}', [CompanyFeatureController::class, 'toggle'])
            ->withoutScopedBindings()
            ->name('companies.features.toggle');

        Route::patch('companies/{company}/toggleMapOption', [CompanyController::class, 'toggleMapOption'])->name('companies.toggleMapOption');
        Route::patch('companies/{company}/toggleDocumentsOption', [CompanyController::class, 'toggleDocumentsOption'])->name('companies.toggleDocumentsOption');
        Route::patch('companies/{company}/toggleActive', [CompanyController::class, 'toggleActive'])->name('companies.toggleActive');
        Route::patch('companies/{company}/setTheme', [CompanyController::class, 'setTheme'])->name('companies.setTheme');
        Route::patch('companies/{company}/setImageAsset', [CompanyController::class, 'setImageAsset'])->name('companies.setImageAsset');
        Route::apiResource('companies', CompanyController::class);

        Route::apiResource('imageTypes', ImageTypesController::class);

        Route::apiResource('images', ImagesController::class);

        Route::apiResource('themes', ThemeController::class);

        Route::post('/impersonate/stop', [ImpersonationController::class, 'stopImpersonate'])
            ->withoutMiddleware(EnsureUserCanImpersonate::class)
            ->name('impersonate.stop');

        Route::as('admin.')
            ->group(function () {
                Route::put('roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions'])
                    ->name('roles.assignPermissions');

                Route::apiResource('roles', RoleController::class);

                Route::patch('users/{user}/role', [UserController::class, 'updateRole'])
                    ->middleware(EnsureSuperAdmin::class)
                    ->name('users.update.role');

                Route::apiResource('users', UserController::class);

                Route::post('/impersonate/{userId}', [ImpersonationController::class, 'impersonate'])
                    ->middleware(EnsureUserCanImpersonate::class)
                    ->name('impersonate.start');

                Route::apiResource('permissions', PermissionController::class)
                    ->middleware(EnsureSuperAdmin::class);

                Route::get('companyApiTokens/validate/{company}', [CompanyApiTokenController::class, 'validateToken'])
                    ->name('companyApiTokens.validate');

                Route::apiResource('companyApiTokens', CompanyApiTokenController::class);

                Route::patch('allowedDomains/{allowedDomain}/toggle-active-status', [AllowedDomainController::class, 'toggleActiveStatus'])
                    ->name('allowedDomains.toggleActiveStatus');

                Route::apiResource('allowedDomains', AllowedDomainController::class);
            });
    });
