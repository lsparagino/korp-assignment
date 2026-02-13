<?php

namespace Database\Factories;

use App\Enums\WalletCurrency;
use App\Enums\WalletStatus;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Wallet>
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
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(['Savings', 'Offshore', 'Business', 'Person A', 'Person B']),
            'currency' => $this->faker->randomElement(WalletCurrency::cases()),
            'status' => $this->faker->randomElement(WalletStatus::cases()),
            'address' => 'bc1q'.Str::lower(Str::random(36)),
        ];
    }
}
