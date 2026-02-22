<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes with strict rate limiting
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.verify');
    Route::post('/accept-invitation/{token}', [InvitationController::class, 'store'])->name('invitation.accept');
});

// Email Verification (public — link works without being logged in)
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/confirm-password', [AuthController::class, 'confirmPassword']);

    // Email Verification
    Route::post('/email/verification-notification', [VerificationController::class, 'resend']);

    // Two Factor Authentication
    Route::post('/user/two-factor-authentication', [TwoFactorController::class, 'store']);
    Route::post('/user/confirmed-two-factor-authentication', [TwoFactorController::class, 'confirm']);
    Route::delete('/user/two-factor-authentication', [TwoFactorController::class, 'destroy']);
    Route::get('/user/two-factor-qr-code', [TwoFactorController::class, 'qrCode']);
    Route::get('/user/two-factor-recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
    Route::post('/user/two-factor-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);

    // Company Routes
    Route::middleware('company')->group(function () {
        // Company Routes
        Route::get('/companies', [CompanyController::class, 'index']);

        // Data Routes
        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::apiResource('transactions', TransactionController::class)->only(['index']);

        // Team Routes
        Route::apiResource('team-members', TeamMemberController::class)->parameters(['team-members' => 'teamMember']);

        // Wallet Routes
        Route::apiResource('wallets', WalletController::class);
        Route::patch('/wallets/{wallet}/toggle-freeze', [WalletController::class, 'toggleFreeze']);
    });

    // Settings Routes
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('/settings/profile', [ProfileController::class, 'destroy'])->name('settings.profile.destroy');
    Route::delete('/settings/pending-email', [ProfileController::class, 'cancelPendingEmail'])->name('settings.pending-email.destroy');
    Route::put('/settings/password', [PasswordController::class, 'update'])->name('settings.password.update');
});

// E2E Testing Routes (only available when APP_ENV=testing)
if (app()->environment('testing')) {
    Route::prefix('test')->group(function () {
        Route::post('/reset-database', [App\Http\Controllers\Api\TestingController::class, 'resetDatabase']);
        Route::post('/create-user', [App\Http\Controllers\Api\TestingController::class, 'createUser']);
        Route::post('/login', [App\Http\Controllers\Api\TestingController::class, 'loginUser']);
    });
}
