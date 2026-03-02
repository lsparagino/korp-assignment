<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'group_id' => Str::uuid()->toString(),
            'wallet_id' => Wallet::factory(),
            'counterpart_wallet_id' => null,
            'type' => $this->faker->randomElement([TransactionType::Credit, TransactionType::Debit]),
            'amount' => function (array $attributes) {
                $magnitude = abs($this->faker->randomFloat(2, 1, 1000));

                return $attributes['type'] === TransactionType::Debit ? -$magnitude : $magnitude;
            },
            'external' => true,
            'reference' => $this->faker->sentence(4),
            'status' => 'completed',
            'source_currency' => 'USD',
            'destination_currency' => 'USD',
            'exchange_rate' => 1.0,
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
