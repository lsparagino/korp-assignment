<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAddressBookEntryRequest;
use App\Http\Requests\Api\UpdateAddressBookEntryRequest;
use App\Http\Resources\AddressBookEntryResource;
use App\Models\AddressBookEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AddressBookEntryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $entries = AddressBookEntry::query()
            ->forUserInCompany($request->user()->id, $request->company_id)
            ->orderBy('name')
            ->get();

        return AddressBookEntryResource::collection($entries);
    }

    public function store(StoreAddressBookEntryRequest $request): AddressBookEntryResource
    {
        $entry = AddressBookEntry::query()->create([
            ...$request->safe()->only(['name', 'address']),
            'user_id' => $request->user()->id,
            'company_id' => $request->company_id,
        ]);

        return new AddressBookEntryResource($entry);
    }

    public function update(UpdateAddressBookEntryRequest $request, AddressBookEntry $addressBookEntry): AddressBookEntryResource
    {
        if ($addressBookEntry->user_id !== $request->user()->id) {
            abort(403);
        }

        $addressBookEntry->update($request->safe()->only(['name', 'address']));

        return new AddressBookEntryResource($addressBookEntry);
    }

    public function destroy(Request $request, AddressBookEntry $addressBookEntry): Response
    {
        if ($addressBookEntry->user_id !== $request->user()->id) {
            abort(403);
        }

        $addressBookEntry->delete();

        return response()->noContent();
    }
}
