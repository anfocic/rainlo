<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DockerTestDataSeeder extends Seeder
{
    /**
     * Simple faker replacement for Docker environment
     */
    private function randomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    private function dateTimeBetween(Carbon $startDate, Carbon $endDate)
    {
        $startTimestamp = $startDate->timestamp;
        $endTimestamp = $endDate->timestamp;
        $randomTimestamp = rand($startTimestamp, $endTimestamp);
        return Carbon::createFromTimestamp($randomTimestamp);
    }

    private function boolean($truePercentage = 50)
    {
        return rand(1, 100) <= $truePercentage;
    }

    private function sentence()
    {
        $words = ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor', 'incididunt'];
        $length = rand(4, 8);
        $sentence = [];
        for ($i = 0; $i < $length; $i++) {
            $sentence[] = $this->randomElement($words);
        }
        return implode(' ', $sentence) . '.';
    }

    private function optional($probability = 0.5)
    {
        return rand(1, 100) <= ($probability * 100) ? $this->sentence() : null;
    }

    private function company()
    {
        $companies = ['TechCorp', 'DataSoft', 'CloudSystems', 'InnovateLab', 'DigitalWorks', 'SmartSolutions', 'NextGen Inc'];
        return $this->randomElement($companies);
    }

    public function run(): void
    {
        // Use database transaction for better performance and rollback capability
        DB::transaction(function () {
            $this->command->info('Creating test users...');

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
                // Check if user already exists
                $existingUser = User::where('email', $userData['email'])->first();
                if ($existingUser) {
                    $this->command->warn("User {$userData['email']} already exists, skipping...");
                    continue;
                }

                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'email_verified_at' => now(),
                ]);

                $this->command->info("Created user: {$user->name}");

                // Generate data based on user profile
                $this->generateUserData($user, $userData['profile']);
            }

            $this->command->info('Test data seeding completed successfully!');
        });
    }

    private function generateUserData(User $user, string $profile): void
    {
        $startDate = Carbon::now()->subMonths(12);
        $endDate = Carbon::now();

        $this->command->info("Generating {$profile} data for {$user->name}...");

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

        $transactions = [];
        for ($i = 0; $i < 15; $i++) {
            $transactions[] = $this->createTransactionArray([
                'user_id' => $user->id,
                'type' => 'income',
                'amount' => rand(2000, 8000),
                'description' => $projects[array_rand($projects)] . ' for ' . $clients[array_rand($clients)],
                'category' => 'Freelance',
                'source' => $clients[array_rand($clients)],
                'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => true,
                'recurring' => false,
                'tax_category' => 'freelance',
                'notes' => rand(1, 10) <= 3 ? $this->sentence() : null,
            ]);
        }

        // Batch insert for better performance
        Transaction::insert($transactions);

        // Generate expenses
        $this->generateFreelancerExpenses($user, $startDate, $endDate);
    }

    private function generateEmployeeData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $transactions = [];

        // Employee Income - regular salary
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $transactions[] = $this->createTransactionArray([
                'user_id' => $user->id,
                'type' => 'income',
                'amount' => 5500,
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
            $transactions[] = $this->createTransactionArray([
                'user_id' => $user->id,
                'type' => 'income',
                'amount' => rand(1000, 3000),
                'description' => $this->randomElement(['Performance Bonus', 'Holiday Bonus', 'Project Completion Bonus']),
                'category' => 'Bonus',
                'source' => 'ABC Corporation',
                'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => false,
                'recurring' => false,
                'tax_category' => 'salary',
                'notes' => 'Annual bonus payment',
            ]);
        }

        Transaction::insert($transactions);
        $this->generateEmployeeExpenses($user, $startDate, $endDate);
    }

    private function generateBusinessOwnerData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $revenueStreams = [
            'Product Sales', 'Service Revenue', 'Consulting', 'Licensing', 'Partnerships',
            'Software Sales', 'SaaS Revenue', 'Client Retainer', 'Project Payment', 'Maintenance Contract',
            'Training Services', 'Support Services', 'Custom Development', 'API Usage Fees', 'Commission'
        ];
        $transactions = [];

        // Generate 60 income transactions for Mike to test infinite scroll
        for ($i = 0; $i < 60; $i++) {
            $transactions[] = $this->createTransactionArray([
                'user_id' => $user->id,
                'type' => 'income',
                'amount' => rand(3000, 15000),
                'description' => $this->randomElement($revenueStreams),
                'category' => 'Business',
                'source' => 'Business Operations',
                'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => true,
                'recurring' => $this->boolean(30),
                'tax_category' => 'business',
                'notes' => $this->optional(0.4),
            ]);
        }

        Transaction::insert($transactions);
        $this->generateBusinessExpenses($user, $startDate, $endDate);
    }

    private function generateInvestorData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $investments = ['Stock Dividends', 'Bond Interest', 'Real Estate', 'Crypto Gains', 'Mutual Funds'];
        $transactions = [];

        for ($i = 0; $i < 20; $i++) {
            $transactions[] = $this->createTransactionArray([
                'user_id' => $user->id,
                'type' => 'income',
                'amount' => rand(500, 5000),
                'description' => $this->randomElement($investments),
                'category' => 'Investment',
                'source' => $this->randomElement(['Brokerage Account', 'Real Estate', 'Crypto Exchange']),
                'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                'is_business' => false,
                'recurring' => $this->boolean(40),
                'tax_category' => 'investment',
                'notes' => $this->optional(0.2),
            ]);
        }

        Transaction::insert($transactions);
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

        $transactions = [];
        foreach ($businessExpenses as $expenseType) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                $transactions[] = $this->createTransactionArray([
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => $this->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => $this->randomElement(['Amazon', 'Best Buy', 'Apple Store', 'Local Vendor']),
                    'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => true,
                    'recurring' => $this->boolean(20),
                    'tax_category' => strtolower($expenseType['category']),
                    'notes' => $this->optional(0.3),
                ]);
            }
        }

        Transaction::insert($transactions);
        $this->generatePersonalExpenses($user, $startDate, $endDate);
    }

    private function generateEmployeeExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $workExpenses = [
            ['category' => 'Transportation', 'items' => ['Gas', 'Parking', 'Public Transit'], 'range' => [20, 150]],
            ['category' => 'Meals', 'items' => ['Business Lunch', 'Client Dinner', 'Conference Meals'], 'range' => [15, 80]],
            ['category' => 'Professional Development', 'items' => ['Course', 'Certification', 'Conference'], 'range' => [100, 1000]],
        ];

        $transactions = [];
        foreach ($workExpenses as $expenseType) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                $transactions[] = $this->createTransactionArray([
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => $this->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => $this->company(),
                    'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => true,
                    'recurring' => false,
                    'tax_category' => strtolower(str_replace(' ', '_', $expenseType['category'])),
                    'notes' => 'Work-related expense',
                ]);
            }
        }

        Transaction::insert($transactions);
        $this->generatePersonalExpenses($user, $startDate, $endDate);
    }

    private function generateBusinessExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $businessExpenseTypes = [
            ['category' => 'Office Rent', 'items' => ['Monthly Office Rent'], 'range' => [1500, 3000], 'recurring' => true],
            ['category' => 'Utilities', 'items' => ['Electricity', 'Internet', 'Phone'], 'range' => [100, 400], 'recurring' => true],
            ['category' => 'Marketing', 'items' => ['Google Ads', 'Facebook Ads', 'Print Materials', 'SEO Tools', 'Social Media Ads'], 'range' => [200, 2000]],
            ['category' => 'Equipment', 'items' => ['Computers', 'Furniture', 'Machinery', 'Monitors', 'Printers'], 'range' => [500, 5000]],
            ['category' => 'Travel', 'items' => ['Business Trip', 'Client Visit', 'Conference', 'Hotel', 'Flight'], 'range' => [300, 1500]],
            ['category' => 'Professional Services', 'items' => ['Legal Fees', 'Accounting', 'Consulting', 'Tax Preparation'], 'range' => [500, 3000]],
            ['category' => 'Software', 'items' => ['CRM Software', 'Project Management', 'Design Tools', 'Analytics'], 'range' => [50, 500]],
            ['category' => 'Office Supplies', 'items' => ['Stationery', 'Paper', 'Ink', 'Cleaning Supplies'], 'range' => [25, 200]],
            ['category' => 'Insurance', 'items' => ['Business Insurance', 'Liability Insurance', 'Equipment Insurance'], 'range' => [200, 800]],
            ['category' => 'Meals & Entertainment', 'items' => ['Client Lunch', 'Team Dinner', 'Business Meeting'], 'range' => [50, 300]],
        ];

        $transactions = [];
        foreach ($businessExpenseTypes as $expenseType) {
            // Increase transaction count for Mike to get more data for infinite scroll
            $count = isset($expenseType['recurring']) && $expenseType['recurring'] ? 12 : rand(4, 12);
            for ($i = 0; $i < $count; $i++) {
                $transactions[] = $this->createTransactionArray([
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => $this->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => $this->company(),
                    'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => true,
                    'recurring' => isset($expenseType['recurring']) ? $expenseType['recurring'] : false,
                    'tax_category' => strtolower(str_replace(' ', '_', $expenseType['category'])),
                    'notes' => $this->optional(0.4),
                ]);
            }
        }

        Transaction::insert($transactions);
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

        $transactions = [];
        foreach ($investmentExpenses as $expenseType) {
            for ($i = 0; $i < rand(1, 4); $i++) {
                $transactions[] = $this->createTransactionArray([
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => $this->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => $this->randomElement(['Fidelity', 'Charles Schwab', 'E*TRADE', 'Local Advisor']),
                    'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => false,
                    'recurring' => $this->boolean(30),
                    'tax_category' => strtolower(str_replace(' ', '_', $expenseType['category'])),
                    'notes' => $this->optional(0.2),
                ]);
            }
        }

        Transaction::insert($transactions);
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

        $transactions = [];
        foreach ($personalExpenses as $expenseType) {
            $count = isset($expenseType['recurring']) && $expenseType['recurring'] ? rand(8, 12) : rand(3, 8);
            for ($i = 0; $i < $count; $i++) {
                $transactions[] = $this->createTransactionArray([
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'amount' => rand($expenseType['range'][0], $expenseType['range'][1]),
                    'description' => $this->randomElement($expenseType['items']),
                    'category' => $expenseType['category'],
                    'vendor' => $this->getVendorForCategory($expenseType['category']),
                    'date' => $this->dateTimeBetween($startDate, $endDate)->format('Y-m-d'),
                    'is_business' => false,
                    'recurring' => isset($expenseType['recurring']) ? $expenseType['recurring'] : false,
                    'tax_category' => 'personal',
                    'notes' => $this->optional(0.2),
                ]);
            }
        }

        Transaction::insert($transactions);
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

        return $this->randomElement($vendors[$category] ?? ['Generic Vendor']);
    }

    /**
     * Create a standardized transaction array with all required fields
     */
    private function createTransactionArray(array $data): array
    {
        return [
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'category' => $data['category'] ?? null,
            'date' => $data['date'],
            'is_business' => $data['is_business'] ?? false,
            'recurring' => $data['recurring'] ?? false,
            'vendor' => $data['vendor'] ?? null,
            'source' => $data['source'] ?? null,
            'tax_category' => $data['tax_category'] ?? null,
            'notes' => $data['notes'] ?? null,
            'receipt_url' => $data['receipt_url'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
