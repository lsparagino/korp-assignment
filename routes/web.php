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

Route::get('transactions', function () {
    return Inertia::render('transactions/Index', [
        'company' => 'Acme Corp',
        'transactions' => [
            [
                'id' => 1,
                'date' => '12/10/2022',
                'wallet' => 'Main Wallet',
                'type' => 'Debit',
                'amount' => -500.00,
                'currency' => 'USD',
                'reference' => 'Invoice #123',
            ],
            [
                'id' => 2,
                'date' => '12/09/2022',
                'wallet' => 'EUR Wallet',
                'type' => 'Credit',
                'amount' => 1000.00,
                'currency' => 'EUR',
                'reference' => 'Client Payment',
            ],
            [
                'id' => 3,
                'date' => '12/08/2022',
                'wallet' => 'Marketing Wallet',
                'type' => 'Debit',
                'amount' => -200.00,
                'currency' => 'USD',
                'reference' => 'Advertising',
            ],
            [
                'id' => 4,
                'date' => '12/07/2022',
                'wallet' => 'Main Wallet',
                'type' => 'Credit',
                'amount' => 2500.00,
                'currency' => 'EUR',
                'reference' => 'Transfer',
            ],
        ],
    ]);
})->middleware(['auth', 'verified'])->name('transactions.index');

Route::get('team-members', function () {
    return Inertia::render('team/Index', [
        'company' => 'Acme Corp',
        'members' => [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'admin@acme.com',
                'role' => 'Admin',
                'wallet_access' => 'All',
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@acme.com',
                'role' => 'Member',
                'wallet_access' => 'Marketing Wallet',
            ],
        ],
    ]);
})->middleware(['auth', 'verified'])->name('team.index');

require __DIR__.'/settings.php';
