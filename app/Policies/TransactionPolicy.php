<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function cancel(User $user, Transaction $transaction): bool
    {
        return $transaction->initiator_user_id === $user->id;
    }
}
