<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function wallets()
    {
        return response()->json([
            'company' => 'Acme Corp',
            'wallets' => [
                ['id' => 1, 'name' => 'Main Wallet', 'currency' => 'USD', 'balance' => 7200.00, 'status' => 'Active'],
                ['id' => 2, 'name' => 'EUR Wallet', 'currency' => 'EUR', 'balance' => 4500.00, 'status' => 'Active'],
                ['id' => 3, 'name' => 'Marketing Wallet', 'currency' => 'USD', 'balance' => 5300.00, 'status' => 'Frozen'],
            ],
        ]);
    }

    public function transactions()
    {
        return response()->json([
            'company' => 'Acme Corp',
            'transactions' => [
                ['id' => 1, 'date' => '12/10/2022', 'wallet' => 'Main Wallet', 'type' => 'Debit', 'amount' => -500.00, 'currency' => 'USD', 'reference' => 'Invoice #123'],
                ['id' => 2, 'date' => '12/09/2022', 'wallet' => 'EUR Wallet', 'type' => 'Credit', 'amount' => 1000.00, 'currency' => 'EUR', 'reference' => 'Client Payment'],
                ['id' => 3, 'date' => '12/08/2022', 'wallet' => 'Marketing Wallet', 'type' => 'Debit', 'amount' => -200.00, 'currency' => 'USD', 'reference' => 'Advertising'],
                ['id' => 4, 'date' => '12/07/2022', 'wallet' => 'Main Wallet', 'type' => 'Credit', 'amount' => 2500.00, 'currency' => 'EUR', 'reference' => 'Transfer'],
            ],
        ]);
    }

    public function team()
    {
        return response()->json([
            'company' => 'Acme Corp',
            'members' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'admin@acme.com', 'role' => 'Admin', 'wallet_access' => 'All'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@acme.com', 'role' => 'Member', 'wallet_access' => 'Marketing Wallet'],
            ],
        ]);
    }

    public function dashboard()
    {
        return response()->json([
            'balances' => [
                ['currency' => 'USD', 'amount' => 25480.50],
                ['currency' => 'EUR', 'amount' => 12750.00],
            ],
            'wallets' => [
                ['name' => 'Main Wallet', 'balance' => 15240.50, 'currency' => 'USD'],
                ['name' => 'EUR Wallet', 'balance' => 12750.00, 'currency' => 'EUR'],
                ['name' => 'Marketing', 'balance' => 10240.00, 'currency' => 'USD'],
            ],
            'transactions' => [
                ['date' => '2024-03-20', 'to' => 'Adobe Systems', 'amount' => -120.00, 'currency' => 'USD', 'type' => 'Debit'],
                ['date' => '2024-03-19', 'to' => 'Client Payment', 'amount' => 2500.00, 'currency' => 'USD', 'type' => 'Credit'],
                ['date' => '2024-03-18', 'to' => 'Marketing Ads', 'amount' => -500.00, 'currency' => 'USD', 'type' => 'Debit'],
            ],
        ]);
    }
}
