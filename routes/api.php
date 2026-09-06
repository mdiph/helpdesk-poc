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

// Route names are prefixed with "api." so they never collide with the
// identically-shaped web resource routes (both would otherwise be "tickets.index").
Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->name('api.')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Lookups
    Route::get('categories', [MetaController::class, 'categories'])->name('categories');
    Route::get('meta', [MetaController::class, 'meta'])->name('meta');
    Route::get('dashboard', [MetaController::class, 'dashboard'])->name('dashboard');

    // Tickets
    Route::apiResource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/comments', [TicketActionController::class, 'comment'])->name('tickets.comments');
    Route::post('tickets/{ticket}/escalate', [TicketActionController::class, 'escalate'])->name('tickets.escalate');
    Route::post('tickets/{ticket}/assign', [TicketActionController::class, 'assign'])->name('tickets.assign');
    Route::post('tickets/{ticket}/status', [TicketActionController::class, 'status'])->name('tickets.status');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports');

    // User management (admin only - enforced by UserPolicy)
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update']);
});
