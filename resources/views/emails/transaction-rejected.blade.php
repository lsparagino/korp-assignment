<x-mail::message>
# {{ __('Transaction Rejected') }}

{{ __('Your transaction has been rejected.') }}

<x-mail::table>
| | |
|:---|:---|
| **{{ __('Amount') }}** | {{ $amount }} {{ $currency }} |
| **{{ __('Wallet') }}** | {{ $walletName }} |
| **{{ __('Rejected by') }}** | {{ $reviewerName }} |
@if($reference)
| **{{ __('Reference') }}** | {{ $reference }} |
@endif
</x-mail::table>

@if($rejectReason)
<x-mail::panel>
**{{ __('Reason') }}:** {{ $rejectReason }}
</x-mail::panel>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
