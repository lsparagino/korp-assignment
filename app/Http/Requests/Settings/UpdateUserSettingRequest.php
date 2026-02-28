<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\Concerns\VerifiesIdentity;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingRequest extends FormRequest
{
    use VerifiesIdentity;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'notify_money_received' => ['sometimes', 'boolean'],
            'notify_money_sent' => ['sometimes', 'boolean'],
            'notify_transaction_approved' => ['sometimes', 'boolean'],
            'notify_transaction_rejected' => ['sometimes', 'boolean'],
            'notify_approval_needed' => ['sometimes', 'boolean'],
            'date_format' => ['sometimes', 'string', 'max:10'],
            'number_format' => ['sometimes', 'string', 'max:10'],
            'daily_transaction_limit' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'security_threshold' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        ];

        if ($this->requiresIdentityVerification()) {
            $rules = array_merge($rules, $this->identityRules());
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        return $this->stripIdentityFields($validated);
    }

    protected function passedValidation(): void
    {
        if (! $this->requiresIdentityVerification()) {
            return;
        }

        $this->verifyIdentity();

        $this->validateThresholdRelation();
    }

    private function requiresIdentityVerification(): bool
    {
        return $this->has('daily_transaction_limit') || $this->has('security_threshold');
    }

    private function validateThresholdRelation(): void
    {
        $dailyLimit = $this->input('daily_transaction_limit')
            ?? $this->user()->setting?->daily_transaction_limit;

        $securityThreshold = $this->input('security_threshold')
            ?? $this->user()->setting?->security_threshold;

        if ($dailyLimit !== null && $securityThreshold !== null && (float) $securityThreshold > (float) $dailyLimit) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'security_threshold' => ['The security threshold must not exceed the daily transaction limit.'],
            ]);
        }
    }
}
