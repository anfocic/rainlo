<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['income', 'expense']);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['Food', 'Transport', 'Entertainment', 'Salary', 'Business', 'Utilities']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'is_business' => $this->faker->boolean(30),
            'recurring' => $this->faker->boolean(20),
            'vendor' => $type === 'expense' ? $this->faker->company() : null,
            'source' => $type === 'income' ? $this->faker->company() : null,
            'tax_category' => $this->faker->randomElement(['Standard', 'Reduced', 'Zero', null]),
            'notes' => $this->faker->optional()->sentence(),
            'receipt_url' => $this->faker->optional()->url(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'income',
            'vendor' => null,
            'source' => $this->faker->company(),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'expense',
            'source' => null,
            'vendor' => $this->faker->company(),
        ]);
    }

    public function business(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_business' => true,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn(array $attributes) => [
            'recurring' => true,
        ]);
    }
}
