<?php

namespace App\Policies;

use App\Models\AddressBookEntry;
use App\Models\User;

class AddressBookEntryPolicy
{
    public function update(User $user, AddressBookEntry $addressBookEntry): bool
    {
        return $addressBookEntry->user_id === $user->id;
    }

    public function delete(User $user, AddressBookEntry $addressBookEntry): bool
    {
        return $addressBookEntry->user_id === $user->id;
    }
}
