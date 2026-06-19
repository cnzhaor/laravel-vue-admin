<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::apiResource('users', UserController::class)->except('show')->middleware('permission:system:user:manage');
        Route::apiResource('roles', RoleController::class)->except('show')->middleware('permission:system:role:manage');

        foreach (['departments', 'positions', 'permissions', 'menus', 'dictionaries', 'dictionary-items', 'parameters'] as $resource) {
            Route::apiResource($resource, ResourceController::class)
                ->parameters([$resource => 'id'])
                ->names([
                    'index' => "{$resource}.index", 'store' => "{$resource}.store",
                    'show' => "{$resource}.show", 'update' => "{$resource}.update",
                    'destroy' => "{$resource}.destroy",
                ])
                ->middleware('permission:system:'.$resource.':manage');
        }

        Route::get('operation-logs', [LogController::class, 'operations'])->middleware('permission:system:operation-logs:manage');
        Route::get('login-logs', [LogController::class, 'logins'])->middleware('permission:system:login-logs:manage');
    });
});
