<?php

use App\Http\Controllers\Api\V1\AccountRoleController;
use App\Http\Controllers\Api\V1\AccountUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Middleware\RequirePasswordChanged;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::get('options', [AuthController::class, 'options'])->middleware('throttle:60,1')->name('options');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth.login')->name('login');
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:auth.refresh')->name('refresh');

        Route::middleware(['web', 'throttle:auth.google'])->group(function (): void {
            Route::get('google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
            Route::get('google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
        });

        Route::middleware(['auth:api', RequirePasswordChanged::class])->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
            Route::put('password', [AuthController::class, 'changePassword'])->middleware('throttle:auth.password')->name('password.update');
        });
    });
});

Route::prefix('v1/accounts')->name('api.v1.accounts.')->middleware(['auth:api', RequirePasswordChanged::class, 'throttle:120,1'])->group(function (): void {
    Route::get('people', [AccountUserController::class, 'people']);
    Route::get('audit', [AccountUserController::class, 'audit']);
    Route::get('users', [AccountUserController::class, 'index']);
    Route::post('users', [AccountUserController::class, 'store']);
    Route::get('users/{user}', [AccountUserController::class, 'show'])->whereNumber('user');
    Route::put('users/{user}', [AccountUserController::class, 'update'])->whereNumber('user');
    Route::post('users/{user}/{action}', [AccountUserController::class, 'action'])->whereNumber('user');
    Route::get('roles', [AccountRoleController::class, 'index']);
    Route::post('roles', [AccountRoleController::class, 'store']);
    Route::put('roles/{role}', [AccountRoleController::class, 'update'])->whereNumber('role');
});
