<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'type' => $this->faker->randomElement([\App\Enums\TransactionType::Credit, \App\Enums\TransactionType::Debit]),
            'amount' => function (array $attributes) {
                // Ensure magnitude is positive, then apply sign based on type
                $magnitude = abs($this->faker->randomFloat(2, 1, 1000));
                return $attributes['type'] === \App\Enums\TransactionType::Debit ? -$magnitude : $magnitude;
            },
            'reference' => $this->faker->sentence(4),
            'from_wallet_id' => \App\Models\Wallet::factory(),
            'to_wallet_id' => function (array $attributes) {
                $fromWallet = \App\Models\Wallet::find($attributes['from_wallet_id']);
                return \App\Models\Wallet::factory()->create([
                    'currency' => $fromWallet?->currency ?? 'USD',
                ])->id;
            },
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
