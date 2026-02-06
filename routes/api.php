<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Auth Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::get('/invitation/{token}', [\App\Http\Controllers\InvitationController::class, 'show'])->name('invitation.verify');
Route::post('/accept-invitation/{token}', [\App\Http\Controllers\InvitationController::class, 'store'])->name('invitation.accept');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/confirm-password', [AuthController::class, 'confirmPassword']);

    // Email Verification
    Route::post('/email/verification-notification', [VerificationController::class, 'resend']);
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');

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
        Route::get('/companies', [\App\Http\Controllers\Api\CompanyController::class, 'index']);

        // Data Routes
        Route::get('/dashboard', [DataController::class, 'dashboard'])->name('dashboard');
        Route::apiResource('transactions', \App\Http\Controllers\Api\TransactionController::class)->only(['index']);

        // Team Routes
        Route::apiResource('team-members', \App\Http\Controllers\Api\TeamMemberController::class);

        // Wallet Routes
        Route::apiResource('wallets', WalletController::class);
        Route::patch('/wallets/{wallet}/toggle-freeze', [WalletController::class, 'toggleFreeze']);
    });

    // Settings Routes
    Route::patch('/settings/profile', [\App\Http\Controllers\Settings\ProfileController::class, 'update']);
    Route::delete('/settings/profile', [\App\Http\Controllers\Settings\ProfileController::class, 'destroy']);
    Route::put('/settings/password', [\App\Http\Controllers\Settings\PasswordController::class, 'update']);
});
