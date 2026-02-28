<x-mail::message>
# {{ __('Transaction Pending Approval') }}

{{ __(':name has initiated a transaction that requires your review.', ['name' => $initiatorName]) }}

<x-mail::table>
| | |
|:---|:---|
| **{{ __('Amount') }}** | {{ $amount }} {{ $currency }} |
| **{{ __('Wallet') }}** | {{ $walletName }} |
| **{{ __('Initiated by') }}** | {{ $initiatorName }} |
@if($reference)
| **{{ __('Reference') }}** | {{ $reference }} |
@endif
</x-mail::table>

@if($notes)
<x-mail::panel>
**{{ __('Notes') }}:** {{ $notes }}
</x-mail::panel>
@endif

<x-mail::button :url="config('app.client_url', config('app.url')) . '/transactions?status=pending_approval'">
{{ __('Review Transaction') }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
