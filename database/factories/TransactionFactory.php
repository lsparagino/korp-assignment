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
        $amount = $this->faker->randomFloat(2, -1000, 1000);
        $type = $amount >= 0 ? \App\Enums\TransactionType::Credit : \App\Enums\TransactionType::Debit;

        return [
            'amount' => $amount,
            'type' => $type,
            'reference' => $this->faker->sentence(4),
            'from_wallet_id' => \App\Models\Wallet::factory(),
            'to_wallet_id' => null, // Optional, can be set in seeder for transfers
        ];
    }
}
