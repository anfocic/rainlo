<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Income>
 */
class IncomeFactory extends Factory
{
    protected $model = Income::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Salary', 'Freelance', 'Business', 'Investment', 'Rental', 'Other'];
        $sources = ['Company ABC', 'Client XYZ', 'Freelance Project', 'Investment Returns', 'Rental Property'];
        $taxCategories = ['salary', 'freelance', 'business', 'investment', 'rental', 'other'];

        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'description' => $this->faker->sentence(6),
            'category' => $this->faker->randomElement($categories),
            'source' => $this->faker->randomElement($sources),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'is_business' => $this->faker->boolean(60), // 60% chance of being business
            'recurring' => $this->faker->boolean(30), // 30% chance of being recurring
            'tax_category' => $this->faker->randomElement($taxCategories),
            'notes' => $this->faker->optional(0.3)->sentence(), // 30% chance of having notes
        ];
    }

    /**
     * Indicate that the income is for business.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_business' => true,
            'category' => $this->faker->randomElement(['Freelance', 'Business', 'Consulting']),
            'tax_category' => $this->faker->randomElement(['freelance', 'business']),
        ]);
    }

    /**
     * Indicate that the income is personal.
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_business' => false,
            'category' => $this->faker->randomElement(['Salary', 'Investment', 'Other']),
            'tax_category' => $this->faker->randomElement(['salary', 'investment', 'other']),
        ]);
    }

    /**
     * Indicate that the income is recurring.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'recurring' => true,
            'description' => 'Monthly ' . $this->faker->randomElement(['salary', 'freelance payment', 'rental income']),
        ]);
    }
}
