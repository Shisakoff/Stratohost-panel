<?php

use App\Http\Controllers\Api\AllocationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DatabaseHostController;
use App\Http\Controllers\Api\EggController;
use App\Http\Controllers\Api\EggVariableController;
use App\Http\Controllers\Api\NestController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\ServerDatabaseController;
use App\Http\Controllers\Api\ServerInstallCallbackController;
use Illuminate\Support\Facades\Route;

// Node -> panel: authenticated with the node's own daemon token, not a
// logged-in admin session. See AuthenticateAgentCallback.
Route::middleware('agent_token')->post(
    '/remote/servers/{uuid}/install',
    ServerInstallCallbackController::class
);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Everything else is the admin Application API - Phase 1 has no
// non-admin-facing API yet.
Route::middleware(['auth:sanctum', 'root_admin'])->group(function () {
    Route::apiResource('nodes', NodeController::class);
    Route::apiResource('nodes.allocations', AllocationController::class)
        ->shallow()
        ->only(['index', 'store', 'destroy']);

    Route::apiResource('nests', NestController::class);
    Route::apiResource('nests.eggs', EggController::class)->shallow();
    Route::apiResource('eggs.variables', EggVariableController::class)
        ->shallow()
        ->parameters(['variables' => 'variable']);

    Route::apiResource('servers', ServerController::class);
    Route::post('/servers/{server}/power', [ServerController::class, 'power']);
    Route::get('/servers/{server}/status', [ServerController::class, 'status']);
    Route::apiResource('servers.databases', ServerDatabaseController::class)
        ->shallow()
        ->only(['index', 'store', 'destroy']);

    Route::apiResource('database-hosts', DatabaseHostController::class);
});
