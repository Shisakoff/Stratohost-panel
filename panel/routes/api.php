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
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Node -> panel: authenticated with the node's own daemon token, not a
// logged-in admin session. See AuthenticateAgentCallback.
Route::middleware('agent_token')->post(
    '/remote/servers/{uuid}/install',
    ServerInstallCallbackController::class
);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);

// Any logged-in user - server/database ownership is enforced inside the
// controllers (ServerPolicy), not at the route level, since admins and
// regular owners share the same endpoints.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable']);
    Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm']);
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable']);

    Route::apiResource('servers', ServerController::class);
    Route::post('/servers/{server}/power', [ServerController::class, 'power']);
    Route::get('/servers/{server}/status', [ServerController::class, 'status']);
    Route::apiResource('servers.databases', ServerDatabaseController::class)
        ->shallow()
        ->only(['index', 'store', 'destroy']);

    Route::apiResource('database-hosts', DatabaseHostController::class);
});

// Infrastructure/content management that has no client-facing use case at
// all - nodes, eggs, and user accounts are entirely admin territory.
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

    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});
