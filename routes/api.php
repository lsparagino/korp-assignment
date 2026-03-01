<?php

use App\Http\Controllers\Api\AddressBookEntryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Public Auth Routes ───────────────────────────────────────────
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::prefix('invitation')->group(function () {
        Route::get('/{token}', [InvitationController::class, 'show'])->name('invitation.verify');
        Route::post('/{token}/accept', [InvitationController::class, 'store'])->name('invitation.accept');
    });
});

// ── Email Verification (public) ──────────────────────────────────
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');

// ── Protected Routes ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // User / Auth
    Route::prefix('user')->group(function () {
        Route::get('/', [AuthController::class, 'user'])->withoutMiddleware('company');
        Route::post('/confirm-password', [AuthController::class, 'confirmPassword']);

        Route::prefix('two-factor')->group(function () {
            Route::post('/authentication', [TwoFactorController::class, 'store']);
            Route::post('/confirmed-authentication', [TwoFactorController::class, 'confirm']);
            Route::delete('/authentication', [TwoFactorController::class, 'destroy']);
            Route::get('/qr-code', [TwoFactorController::class, 'qrCode']);
            Route::get('/recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
            Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
        });
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [VerificationController::class, 'resend']);

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/preferences', [SettingController::class, 'showUserSettings'])->name('settings.preferences.show');
        Route::put('/preferences', [SettingController::class, 'updateUserSettings'])->name('settings.preferences.update');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('settings.profile.destroy');
        Route::delete('/pending-email', [ProfileController::class, 'cancelPendingEmail'])->name('settings.pending-email.destroy');
        Route::put('/password', [PasswordController::class, 'update'])->name('settings.password.update');

        // Company-scoped thresholds
        Route::middleware('company')->group(function () {
            Route::get('/thresholds', [SettingController::class, 'indexCompanyThresholds'])->name('settings.thresholds.index');
            Route::put('/thresholds', [SettingController::class, 'upsertCompanyThreshold'])->name('settings.thresholds.upsert');
            Route::delete('/thresholds/{threshold}', [SettingController::class, 'destroyCompanyThreshold'])->name('settings.thresholds.destroy');
        });
    });

    // Company-scoped Routes
    Route::middleware('company')->group(function () {
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::get('/currencies', [CompanyController::class, 'currencies']);
        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

        Route::apiResource('transactions', TransactionController::class)->only(['index']);

        Route::prefix('transfers')->group(function () {
            Route::post('/', [TransferController::class, 'store']);
            Route::post('/{groupId}/review', [TransferController::class, 'review']);
            Route::post('/{groupId}/cancel', [TransferController::class, 'cancel']);
        });

        Route::prefix('team-members')->group(function () {
            Route::apiResource('/', TeamMemberController::class)->parameters(['' => 'teamMember']);
            Route::patch('/{teamMember}/promote', [TeamMemberController::class, 'promote']);
        });

        Route::prefix('wallets')->group(function () {
            Route::apiResource('/', WalletController::class)->parameters(['' => 'wallet']);
            Route::patch('/{wallet}/toggle-freeze', [WalletController::class, 'toggleFreeze']);
        });

        Route::apiResource('address-book', AddressBookEntryController::class)
            ->parameters(['address-book' => 'addressBookEntry'])
            ->except(['show']);
    });
});

// ── E2E Testing Routes ───────────────────────────────────────────
if (app()->environment('testing')) {
    Route::prefix('test')->group(function () {
        Route::post('/reset-database', [App\Http\Controllers\Api\TestingController::class, 'resetDatabase']);
        Route::post('/create-user', [App\Http\Controllers\Api\TestingController::class, 'createUser']);
        Route::post('/login', [App\Http\Controllers\Api\TestingController::class, 'loginUser']);
        Route::post('/create-password-reset-token', [App\Http\Controllers\Api\TestingController::class, 'createPasswordResetToken']);
        Route::post('/create-second-company', [App\Http\Controllers\Api\TestingController::class, 'createSecondCompany']);
        Route::post('/create-wallet', [App\Http\Controllers\Api\TestingController::class, 'createWallet']);
    });
}
