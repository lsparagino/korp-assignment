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
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

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
    Route::delete('/user/two-factor-authentication', [TwoFactorController::class, 'destroy']);
    Route::get('/user/two-factor-qr-code', [TwoFactorController::class, 'qrCode']);
    Route::get('/user/two-factor-recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
    Route::post('/user/two-factor-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);

    // Data Routes
    Route::get('/dashboard', [DataController::class, 'dashboard']);
    Route::get('/transactions', [\App\Http\Controllers\Api\TransactionController::class, 'index']);
    Route::get('/team-members', [DataController::class, 'team']);

    // Wallet Routes
    Route::apiResource('wallets', WalletController::class);
    Route::patch('/wallets/{wallet}/toggle-freeze', [WalletController::class, 'toggleFreeze']);
});
