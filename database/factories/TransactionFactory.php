<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
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
            'type' => $this->faker->randomElement([TransactionType::Credit, TransactionType::Debit]),
            'amount' => function (array $attributes) {
                // Ensure magnitude is positive, then apply sign based on type
                $magnitude = abs($this->faker->randomFloat(2, 1, 1000));

                return $attributes['type'] === TransactionType::Debit ? -$magnitude : $magnitude;
            },
            'reference' => $this->faker->sentence(4),
            'from_wallet_id' => Wallet::factory(),
            'to_wallet_id' => function (array $attributes) {
                $fromWallet = Wallet::find($attributes['from_wallet_id']);

                return Wallet::factory()->create([
                    'currency' => $fromWallet?->currency ?? 'USD',
                ])->id;
            },
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
