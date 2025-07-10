<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Office', 'Travel', 'Meals', 'Software', 'Equipment', 'Marketing', 'Utilities', 'Other'];
        $vendors = ['Amazon', 'Office Depot', 'Starbucks', 'Adobe', 'Microsoft', 'Google', 'Apple', 'Local Vendor'];
        $taxCategories = ['office_supplies', 'software', 'equipment', 'travel', 'meals', 'marketing', 'utilities', 'other'];

        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 2000),
            'description' => $this->faker->sentence(4),
            'category' => $this->faker->randomElement($categories),
            'vendor' => $this->faker->randomElement($vendors),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'is_business' => $this->faker->boolean(70), // 70% chance of being business
            'recurring' => $this->faker->boolean(20), // 20% chance of being recurring
            'tax_deductible' => $this->faker->boolean(60), // 60% chance of being tax deductible
            'tax_category' => $this->faker->randomElement($taxCategories),
            'receipt_url' => $this->faker->optional(0.4)->url(), // 40% chance of having receipt
            'notes' => $this->faker->optional(0.3)->sentence(), // 30% chance of having notes
        ];
    }

    /**
     * Indicate that the expense is for business.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_business' => true,
            'tax_deductible' => true,
            'category' => $this->faker->randomElement(['Office', 'Software', 'Equipment', 'Marketing']),
            'tax_category' => $this->faker->randomElement(['office_supplies', 'software', 'equipment', 'marketing']),
        ]);
    }

    /**
     * Indicate that the expense is tax deductible.
     */
    public function taxDeductible(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_deductible' => true,
            'is_business' => true,
        ]);
    }

    /**
     * Indicate that the expense has a receipt.
     */
    public function withReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_url' => 'receipts/' . $this->faker->uuid() . '.pdf',
        ]);
    }
}
