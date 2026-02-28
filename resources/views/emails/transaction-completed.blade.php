<x-mail::message>
# {{ __('Transaction Completed') }}

{{ __('Your transaction has been completed successfully.') }}

<x-mail::table>
| | |
|:---|:---|
| **{{ __('Amount') }}** | {{ $amount }} {{ $currency }} |
| **{{ __('Wallet') }}** | {{ $walletName }} |
@if($reference)
| **{{ __('Reference') }}** | {{ $reference }} |
@endif
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
