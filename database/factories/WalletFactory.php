<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->randomElement(['Savings', 'Offshore', 'Business', 'Person A', 'Person B']),
            'currency' => $this->faker->randomElement(\App\Enums\WalletCurrency::cases()),
            'status' => $this->faker->randomElement(\App\Enums\WalletStatus::cases()),
        ];
    }
}
