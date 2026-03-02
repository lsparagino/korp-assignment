<?php

namespace App\Services;

use App\Models\AddressBookEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AddressBookEntryService
{
    public function list(int $userId, int $companyId): Collection
    {
        return AddressBookEntry::query()
            ->forUserInCompany($userId, $companyId)
            ->orderBy('name')
            ->get();
    }

    public function create(User $user, int $companyId, array $data): AddressBookEntry
    {
        return AddressBookEntry::query()->create([
            ...$data,
            'user_id' => $user->id,
            'company_id' => $companyId,
        ]);
    }

    public function update(AddressBookEntry $entry, array $data): AddressBookEntry
    {
        $entry->update($data);

        return $entry;
    }

    public function delete(AddressBookEntry $entry): void
    {
        $entry->delete();
    }
}
