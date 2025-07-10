<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ComprehensiveTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create different user profiles
        $users = [
            [
                'name' => 'John Freelancer',
                'email' => 'john@freelancer.com',
                'password' => 'password',
                'profile' => 'freelancer'
            ],
            [
                'name' => 'Sarah Employee',
                'email' => 'sarah@employee.com',
                'password' => 'password',
                'profile' => 'employee'
            ],
            [
                'name' => 'Mike Business Owner',
                'email' => 'mike@business.com',
                'password' => 'password',
                'profile' => 'business_owner'
            ],
            [
                'name' => 'Lisa Investor',
                'email' => 'lisa@investor.com',
                'password' => 'password',
                'profile' => 'investor'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'email_verified_at' => now(),
            ]);

            // Generate data based on user profile
            $this->generateUserData($user, $userData['profile']);
        }
    }

    private function generateUserData(User $user, string $profile): void
    {
        $startDate = Carbon::now()->subMonths(12);
        $endDate = Carbon::now();

        switch ($profile) {
            case 'freelancer':
                $this->generateFreelancerData($user, $startDate, $endDate);
                break;
            case 'employee':
                $this->generateEmployeeData($user, $startDate, $endDate);
                break;
            case 'business_owner':
                $this->generateBusinessOwnerData($user, $startDate, $endDate);
                break;
            case 'investor':
                $this->generateInvestorData($user, $startDate, $endDate);
                break;
        }
    }

    private function generateFreelancerData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        // Freelancer Income - irregular but substantial
        $clients = ['TechCorp Inc', 'StartupXYZ', 'Digital Agency', 'E-commerce Co', 'Consulting Firm'];
        $projects = ['Website Development', 'Mobile App', 'API Integration', 'Database Design', 'UI/UX Design'];

        for ($i = 0; $i < 15; $i++) {
            Income::create([
                'user_id' => $user->id,
                'amount' => rand(2000, 8000),
                'description' => fake()->randomElement($projects) . ' for ' . fake()->randomElement($clients),
                'category' => 'Freelance',
                'source' => fake()->randomElement($clients),
                'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => true,
                'recurring' => false,
                'tax_category' => 'freelance',
                'notes' => fake()->optional(0.3)->sentence(),
            ]);
        }

        // Freelancer Expenses
        $this->generateFreelancerExpenses($user, $startDate, $endDate);
    }

    private function generateEmployeeData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        // Employee Income - regular salary
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            Income::create([
                'user_id' => $user->id,
                'amount' => 5500, // Monthly salary
                'description' => 'Monthly Salary',
                'category' => 'Salary',
                'source' => 'ABC Corporation',
                'date' => $currentDate->format('Y-m-d'),
                'is_business' => false,
                'recurring' => true,
                'tax_category' => 'salary',
                'notes' => 'Regular monthly salary payment',
            ]);
            $currentDate->addMonth();
        }

        // Occasional bonuses
        for ($i = 0; $i < 3; $i++) {
            Income::create([
                'user_id' => $user->id,
                'amount' => rand(1000, 3000),
                'description' => fake()->randomElement(['Performance Bonus', 'Holiday Bonus', 'Project Completion Bonus']),
                'category' => 'Bonus',
                'source' => 'ABC Corporation',
                'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => false,
                'recurring' => false,
                'tax_category' => 'salary',
            ]);
        }

        $this->generateEmployeeExpenses($user, $startDate, $endDate);
    }

    private function generateBusinessOwnerData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        // Business Income - varied revenue streams
        $revenueStreams = ['Product Sales', 'Service Revenue', 'Consulting', 'Licensing', 'Partnerships'];

        for ($i = 0; $i < 25; $i++) {
            Income::create([
                'user_id' => $user->id,
                'amount' => rand(3000, 15000),
                'description' => fake()->randomElement($revenueStreams),
                'category' => 'Business',
                'source' => 'Business Operations',
                'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => true,
                'recurring' => fake()->boolean(30),
                'tax_category' => 'business',
                'notes' => fake()->optional(0.4)->sentence(),
            ]);
        }

        $this->generateBusinessExpenses($user, $startDate, $endDate);
    }

    private function generateInvestorData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        // Investment Income
        $investments = ['Stock Dividends', 'Bond Interest', 'Real Estate', 'Crypto Gains', 'Mutual Funds'];

        for ($i = 0; $i < 20; $i++) {
            Income::create([
                'user_id' => $user->id,
                'amount' => rand(500, 5000),
                'description' => fake()->randomElement($investments),
                'category' => 'Investment',
                'source' => fake()->randomElement(['Brokerage Account', 'Real Estate', 'Crypto Exchange']),
                'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => false,
                'recurring' => fake()->boolean(40),
                'tax_category' => 'investment',
                'notes' => fake()->optional(0.2)->sentence(),
            ]);
        }

        $this->generateInvestorExpenses($user, $startDate, $endDate);
    }

    private function generateFreelancerExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $businessExpenses = [
            ['category' => 'Software', 'items' => ['Adobe Creative Suite', 'Figma Pro', 'Slack Premium', 'GitHub Pro'], 'range' => [20, 100]],
            ['category' => 'Equipment', 'items' => ['MacBook Pro', 'Monitor', 'Keyboard', 'Mouse', 'Webcam'], 'range' => [50, 2000]],
            ['category' => 'Office', 'items' => ['Desk', 'Chair', 'Lighting', 'Storage'], 'range' => [30, 500]],
            ['category' => 'Marketing', 'items' => ['Website Hosting', 'Domain', 'Business Cards', 'Portfolio'], 'range' => [25, 300]],
        ];

        foreach ($businessExpenses as $expenseType) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                Expense::create([
                    'user_id' => $user->id,
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => fake()->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => fake()->randomElement(['Amazon', 'Best Buy', 'Apple Store', 'Local Vendor']),
                    'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => true,
                    'recurring' => fake()->boolean(20),
                    'tax_deductible' => true,
                    'tax_category' => strtolower($expenseType['category']),
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
            }
        }

        // Personal expenses
        $this->generatePersonalExpenses($user, $startDate, $endDate);
    }

    private function generateEmployeeExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        // Work-related expenses
        $workExpenses = [
            ['category' => 'Transportation', 'items' => ['Gas', 'Parking', 'Public Transit'], 'range' => [20, 150]],
            ['category' => 'Meals', 'items' => ['Business Lunch', 'Client Dinner', 'Conference Meals'], 'range' => [15, 80]],
            ['category' => 'Professional Development', 'items' => ['Course', 'Certification', 'Conference'], 'range' => [100, 1000]],
        ];

        foreach ($workExpenses as $expenseType) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                Expense::create([
                    'user_id' => $user->id,
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => fake()->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => fake()->company(),
                    'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => true,
                    'recurring' => false,
                    'tax_deductible' => true,
                    'tax_category' => strtolower(str_replace(' ', '_', $expenseType['category'])),
                    'notes' => 'Work-related expense',
                ]);
            }
        }

        $this->generatePersonalExpenses($user, $startDate, $endDate);
    }

    private function generateBusinessExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $businessExpenseTypes = [
            ['category' => 'Office Rent', 'items' => ['Monthly Office Rent'], 'range' => [1500, 3000], 'recurring' => true],
            ['category' => 'Utilities', 'items' => ['Electricity', 'Internet', 'Phone'], 'range' => [100, 400], 'recurring' => true],
            ['category' => 'Marketing', 'items' => ['Google Ads', 'Facebook Ads', 'Print Materials'], 'range' => [200, 2000]],
            ['category' => 'Equipment', 'items' => ['Computers', 'Furniture', 'Machinery'], 'range' => [500, 5000]],
            ['category' => 'Travel', 'items' => ['Business Trip', 'Client Visit', 'Conference'], 'range' => [300, 1500]],
            ['category' => 'Professional Services', 'items' => ['Legal Fees', 'Accounting', 'Consulting'], 'range' => [500, 3000]],
        ];

        foreach ($businessExpenseTypes as $expenseType) {
            $count = isset($expenseType['recurring']) && $expenseType['recurring'] ? 12 : rand(2, 8);
            for ($i = 0; $i < $count; $i++) {
                Expense::create([
                    'user_id' => $user->id,
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => fake()->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => fake()->company(),
                    'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => true,
                    'recurring' => isset($expenseType['recurring']) ? $expenseType['recurring'] : false,
                    'tax_deductible' => true,
                    'tax_category' => strtolower(str_replace(' ', '_', $expenseType['category'])),
                    'notes' => fake()->optional(0.4)->sentence(),
                ]);
            }
        }

        $this->generatePersonalExpenses($user, $startDate, $endDate);
    }

    private function generateInvestorExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $investmentExpenses = [
            ['category' => 'Investment Fees', 'items' => ['Brokerage Fees', 'Management Fees', 'Transaction Costs'], 'range' => [25, 200]],
            ['category' => 'Professional Services', 'items' => ['Financial Advisor', 'Tax Preparation', 'Legal Consultation'], 'range' => [200, 1000]],
            ['category' => 'Education', 'items' => ['Investment Course', 'Financial Books', 'Seminar'], 'range' => [50, 500]],
            ['category' => 'Technology', 'items' => ['Trading Software', 'Market Data', 'Analysis Tools'], 'range' => [30, 300]],
        ];

        foreach ($investmentExpenses as $expenseType) {
            for ($i = 0; $i < rand(1, 4); $i++) {
                Expense::create([
                    'user_id' => $user->id,
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => fake()->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => fake()->randomElement(['Fidelity', 'Charles Schwab', 'E*TRADE', 'Local Advisor']),
                    'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => false,
                    'recurring' => fake()->boolean(30),
                    'tax_deductible' => fake()->boolean(70),
                    'tax_category' => strtolower(str_replace(' ', '_', $expenseType['category'])),
                    'notes' => fake()->optional(0.2)->sentence(),
                ]);
            }
        }

        $this->generatePersonalExpenses($user, $startDate, $endDate);
    }

    private function generatePersonalExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $personalExpenses = [
            ['category' => 'Groceries', 'items' => ['Weekly Groceries', 'Organic Food', 'Specialty Items'], 'range' => [80, 200], 'recurring' => true],
            ['category' => 'Dining', 'items' => ['Restaurant', 'Fast Food', 'Coffee Shop', 'Delivery'], 'range' => [15, 100]],
            ['category' => 'Transportation', 'items' => ['Gas', 'Car Maintenance', 'Insurance', 'Registration'], 'range' => [50, 400]],
            ['category' => 'Entertainment', 'items' => ['Movies', 'Concerts', 'Streaming Services', 'Games'], 'range' => [10, 150]],
            ['category' => 'Healthcare', 'items' => ['Doctor Visit', 'Pharmacy', 'Dental', 'Vision'], 'range' => [50, 300]],
            ['category' => 'Utilities', 'items' => ['Electricity', 'Water', 'Internet', 'Phone'], 'range' => [80, 250], 'recurring' => true],
            ['category' => 'Shopping', 'items' => ['Clothing', 'Electronics', 'Home Goods', 'Personal Care'], 'range' => [25, 500]],
        ];

        foreach ($personalExpenses as $expenseType) {
            $count = isset($expenseType['recurring']) && $expenseType['recurring'] ? rand(8, 12) : rand(3, 8);
            for ($i = 0; $i < $count; $i++) {
                Expense::create([
                    'user_id' => $user->id,
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => fake()->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => $this->getVendorForCategory($expenseType['category']),
                    'date' => fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => false,
                    'recurring' => isset($expenseType['recurring']) ? $expenseType['recurring'] : false,
                    'tax_deductible' => false,
                    'tax_category' => 'personal',
                    'notes' => fake()->optional(0.2)->sentence(),
                ]);
            }
        }
    }

    private function getVendorForCategory(string $category): string
    {
        $vendors = [
            'Groceries' => ['Whole Foods', 'Safeway', 'Trader Joes', 'Costco'],
            'Dining' => ['McDonalds', 'Starbucks', 'Local Restaurant', 'Pizza Hut'],
            'Transportation' => ['Shell', 'Chevron', 'Auto Shop', 'DMV'],
            'Entertainment' => ['Netflix', 'Spotify', 'AMC Theaters', 'Steam'],
            'Healthcare' => ['CVS Pharmacy', 'Walgreens', 'Medical Center', 'Dental Office'],
            'Utilities' => ['PG&E', 'Comcast', 'Verizon', 'Water Company'],
            'Shopping' => ['Amazon', 'Target', 'Best Buy', 'Macys'],
        ];

        return fake()->randomElement($vendors[$category] ?? ['Generic Vendor']);
    }
}
