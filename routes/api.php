<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketActionController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| REST API (token auth via Laravel Sanctum)
|---------------------------------------------------------------------------
| Obtain a token:  POST /api/auth/login  { email, password }
| Then send:       Authorization: Bearer <token>
|
| Every endpoint below is additionally constrained by the same policies and
| query scopes used by the web UI, so RBAC is enforced identically.
*/

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Lookups
    Route::get('categories', [MetaController::class, 'categories']);
    Route::get('meta', [MetaController::class, 'meta']);
    Route::get('dashboard', [MetaController::class, 'dashboard']);

    // Tickets
    Route::apiResource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/comments', [TicketActionController::class, 'comment']);
    Route::post('tickets/{ticket}/escalate', [TicketActionController::class, 'escalate']);
    Route::post('tickets/{ticket}/assign', [TicketActionController::class, 'assign']);
    Route::post('tickets/{ticket}/status', [TicketActionController::class, 'status']);

    // Reports
    Route::get('reports', [ReportController::class, 'index']);

    // User management (admin only - enforced by UserPolicy)
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update']);
});
