<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Laravel is now exclusively a REST API backend.
// All API routes are defined in routes/api.php under the /api/v0 prefix.
// Invitation Routes
Route::get('/invitation/{token}', [\App\Http\Controllers\InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitation/{token}', [\App\Http\Controllers\InvitationController::class, 'store'])->name('invitation.store');
