<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\AttachmentController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\TicketAssignmentController;
use App\Http\Controllers\Web\TicketCommentController;
use App\Http\Controllers\Web\TicketController;
use App\Http\Controllers\Web\TicketEscalationController;
use App\Http\Controllers\Web\TicketExportController;
use App\Http\Controllers\Web\TicketStatusController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// ---------------------------------------------------------------------------
// Guest
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

// ---------------------------------------------------------------------------
// Authenticated
// ---------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Ticket exports (must be declared before the {ticket} wildcard routes).
    Route::get('tickets/export', [TicketExportController::class, 'index'])->name('tickets.export');
    Route::get('tickets/{ticket}/export', [TicketExportController::class, 'show'])->name('tickets.export.show');

    // Ticket CRUD
    Route::resource('tickets', TicketController::class);

    // Ticket workflow actions
    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::post('tickets/{ticket}/escalate', [TicketEscalationController::class, 'store'])->name('tickets.escalate');
    Route::put('tickets/{ticket}/assignee', [TicketAssignmentController::class, 'update'])->name('tickets.assign');
    Route::put('tickets/{ticket}/status', [TicketStatusController::class, 'update'])->name('tickets.status');

    // Attachments
    Route::post('tickets/{ticket}/attachments', [AttachmentController::class, 'store'])->name('tickets.attachments.store');
    Route::get('tickets/{ticket}/attachments/{attachment}', [AttachmentController::class, 'show'])->name('tickets.attachments.show');
    Route::delete('tickets/{ticket}/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('tickets.attachments.destroy');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Profile (self-service password change)
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // User management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::put('users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });
});
