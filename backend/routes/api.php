<?php

use App\Http\Controllers\Api\V1\AccountRoleController;
use App\Http\Controllers\Api\V1\AccountUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\MasterImportController;
use App\Http\Controllers\Api\V1\MasterRelationController;
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

Route::prefix('v1/master')->name('api.v1.master.')->middleware(['auth:api', RequirePasswordChanged::class, 'throttle:120,1'])->group(function (): void {
    Route::get('options', [MasterDataController::class, 'options'])->name('options');
    Route::get('people', [MasterDataController::class, 'people'])->name('people');
    Route::get('institution', [MasterDataController::class, 'institution'])->name('institution.show');
    Route::put('institution', [MasterDataController::class, 'saveInstitution'])->name('institution.update');
    Route::get('imports/templates/{resource}', [MasterImportController::class, 'template'])->name('imports.template');
    Route::get('imports', [MasterImportController::class, 'index'])->name('imports.index');
    Route::post('imports', [MasterImportController::class, 'store'])->middleware('throttle:10,1,master-import:')->name('imports.store');
    Route::get('imports/{batch}', [MasterImportController::class, 'show'])->whereNumber('batch')->name('imports.show');
    Route::get('imports/{batch}/rows', [MasterImportController::class, 'rows'])->whereNumber('batch')->name('imports.rows');
    Route::get('imports/{batch}/errors', [MasterImportController::class, 'errors'])->whereNumber('batch')->name('imports.errors');
    Route::post('imports/{batch}/confirm', [MasterImportController::class, 'confirm'])->whereNumber('batch')->name('imports.confirm');

    Route::prefix('students/{owner}')->whereNumber('owner')->group(function (): void {
        Route::get('{relation}', [MasterRelationController::class, 'index'])->where('relation', 'guardian-links')->name('guardian-links.index');
        Route::post('{relation}', [MasterRelationController::class, 'store'])->where('relation', 'guardian-links')->name('guardian-links.store');
        Route::post('{relation}/{link}/{action}', [MasterRelationController::class, 'action'])->where(['relation' => 'guardian-links', 'link' => '[0-9]+', 'action' => 'verify|revoke|end'])->name('guardian-links.action');
    });
    Route::prefix('employees/{owner}')->whereNumber('owner')->group(function (): void {
        Route::get('{relation}', [MasterRelationController::class, 'index'])->where('relation', 'categories|positions')->name('employee-relations.index');
        Route::post('{relation}', [MasterRelationController::class, 'store'])->where('relation', 'categories|positions')->name('employee-relations.store');
        Route::post('{relation}/{link}/{action}', [MasterRelationController::class, 'action'])->where(['relation' => 'categories|positions', 'link' => '[0-9]+', 'action' => 'end'])->name('employee-relations.action');
    });

    Route::group(['where' => ['resource' => 'students|guardians|employees|positions|employee-categories|subjects|student-statuses|attendance-statuses']], function (): void {
        Route::get('{resource}', [MasterDataController::class, 'index'])->name('index');
        Route::post('{resource}', [MasterDataController::class, 'store'])->name('store');
        Route::get('{resource}/{record}', [MasterDataController::class, 'show'])->whereNumber('record')->name('show');
        Route::put('{resource}/{record}', [MasterDataController::class, 'update'])->whereNumber('record')->name('update');
        Route::post('{resource}/{record}/status', [MasterDataController::class, 'status'])->whereNumber('record')->name('status');
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
