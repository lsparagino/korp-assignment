<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('wallets', function () {
    return Inertia::render('wallets/Index', [
        'company' => 'Acme Corp',
        'wallets' => [
            [
                'id' => 1,
                'name' => 'Main Wallet',
                'currency' => 'USD',
                'balance' => 7200.00,
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'name' => 'EUR Wallet',
                'currency' => 'EUR',
                'balance' => 4500.00,
                'status' => 'Active',
            ],
            [
                'id' => 3,
                'name' => 'Marketing Wallet',
                'currency' => 'USD',
                'balance' => 5300.00,
                'status' => 'Frozen',
            ],
        ],
    ]);
})->middleware(['auth', 'verified'])->name('wallets.index');

Route::get('wallets/create', function () {
    return Inertia::render('wallets/Create', [
        'company' => 'Acme Corp',
    ]);
})->middleware(['auth', 'verified'])->name('wallets.create');

require __DIR__.'/settings.php';
