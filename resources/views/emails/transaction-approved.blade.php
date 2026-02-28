<x-mail::message>
# {{ __('Transaction Approved') }}

{{ __('Your transaction has been approved and completed.') }}

<x-mail::table>
| | |
|:---|:---|
| **{{ __('Amount') }}** | {{ $amount }} {{ $currency }} |
| **{{ __('Wallet') }}** | {{ $walletName }} |
| **{{ __('Approved by') }}** | {{ $reviewerName }} |
@if($reference)
| **{{ __('Reference') }}** | {{ $reference }} |
@endif
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
