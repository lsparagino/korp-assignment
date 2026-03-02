<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAddressBookEntryRequest;
use App\Http\Requests\Api\UpdateAddressBookEntryRequest;
use App\Http\Resources\AddressBookEntryResource;
use App\Models\AddressBookEntry;
use App\Services\AddressBookEntryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AddressBookEntryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private AddressBookEntryService $addressBookEntryService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressBookEntryResource::collection(
            $this->addressBookEntryService->list($request->user()->id, $request->company_id)
        );
    }

    public function store(StoreAddressBookEntryRequest $request): AddressBookEntryResource
    {
        $entry = $this->addressBookEntryService->create(
            $request->user(),
            $request->company_id,
            $request->safe()->only(['name', 'address'])
        );

        return new AddressBookEntryResource($entry);
    }

    public function update(UpdateAddressBookEntryRequest $request, AddressBookEntry $addressBookEntry): AddressBookEntryResource
    {
        $this->authorize('update', $addressBookEntry);

        return new AddressBookEntryResource(
            $this->addressBookEntryService->update($addressBookEntry, $request->safe()->only(['name', 'address']))
        );
    }

    public function destroy(Request $request, AddressBookEntry $addressBookEntry): Response
    {
        $this->authorize('delete', $addressBookEntry);

        $this->addressBookEntryService->delete($addressBookEntry);

        return response()->noContent();
    }
}
