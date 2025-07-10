<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SimpleTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
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
            ]
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'email_verified_at' => now(),
            ]);

            $this->generateUserData($user, $userData['profile']);
        }
    }

    private function generateUserData(User $user, string $profile): void
    {
        $startDate = Carbon::now()->subMonths(6);
        $endDate = Carbon::now();

        // Generate income based on profile
        if ($profile === 'freelancer') {
            $this->generateFreelancerData($user, $startDate, $endDate);
        } elseif ($profile === 'employee') {
            $this->generateEmployeeData($user, $startDate, $endDate);
        } else {
            $this->generateBusinessData($user, $startDate, $endDate);
        }

        // Generate common expenses
        $this->generateCommonExpenses($user, $startDate, $endDate);
    }

    private function generateFreelancerData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $projects = ['Website Development', 'Mobile App', 'API Integration', 'UI/UX Design'];
        $clients = ['TechCorp Inc', 'StartupXYZ', 'Digital Agency', 'E-commerce Co'];
        
        for ($i = 0; $i < 8; $i++) {
            Income::create([
                'user_id' => $user->id,
                'amount' => rand(2000, 6000),
                'description' => $projects[array_rand($projects)] . ' for ' . $clients[array_rand($clients)],
                'category' => 'Freelance',
                'source' => $clients[array_rand($clients)],
                'date' => $this->randomDate($startDate, $endDate),
                'is_business' => true,
                'recurring' => false,
                'tax_category' => 'freelance',
            ]);
        }
    }

    private function generateEmployeeData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        // Monthly salary
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            Income::create([
                'user_id' => $user->id,
                'amount' => 5500,
                'description' => 'Monthly Salary',
                'category' => 'Salary',
                'source' => 'ABC Corporation',
                'date' => $currentDate->format('Y-m-d'),
                'is_business' => false,
                'recurring' => true,
                'tax_category' => 'salary',
            ]);
            $currentDate->addMonth();
        }

        // Bonus
        Income::create([
            'user_id' => $user->id,
            'amount' => 2000,
            'description' => 'Performance Bonus',
            'category' => 'Bonus',
            'source' => 'ABC Corporation',
            'date' => $this->randomDate($startDate, $endDate),
            'is_business' => false,
            'recurring' => false,
            'tax_category' => 'salary',
        ]);
    }

    private function generateBusinessData(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $revenueStreams = ['Product Sales', 'Service Revenue', 'Consulting', 'Licensing'];
        
        for ($i = 0; $i < 12; $i++) {
            Income::create([
                'user_id' => $user->id,
                'amount' => rand(5000, 15000),
                'description' => $revenueStreams[array_rand($revenueStreams)],
                'category' => 'Business',
                'source' => 'Business Operations',
                'date' => $this->randomDate($startDate, $endDate),
                'is_business' => true,
                'recurring' => $i % 3 === 0,
                'tax_category' => 'business',
            ]);
        }
    }

    private function generateCommonExpenses(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $expenses = [
            ['category' => 'Software', 'description' => 'Adobe Creative Suite', 'amount' => 50, 'business' => true],
            ['category' => 'Equipment', 'description' => 'MacBook Pro', 'amount' => 2000, 'business' => true],
            ['category' => 'Office', 'description' => 'Desk Chair', 'amount' => 300, 'business' => true],
            ['category' => 'Groceries', 'description' => 'Weekly Groceries', 'amount' => 150, 'business' => false],
            ['category' => 'Dining', 'description' => 'Restaurant', 'amount' => 75, 'business' => false],
            ['category' => 'Transportation', 'description' => 'Gas', 'amount' => 60, 'business' => false],
            ['category' => 'Entertainment', 'description' => 'Netflix', 'amount' => 15, 'business' => false],
            ['category' => 'Healthcare', 'description' => 'Doctor Visit', 'amount' => 200, 'business' => false],
            ['category' => 'Utilities', 'description' => 'Electricity', 'amount' => 120, 'business' => false],
        ];

        foreach ($expenses as $expense) {
            for ($i = 0; $i < rand(1, 4); $i++) {
                Expense::create([
                    'user_id' => $user->id,
                    'amount' => $expense['amount'] + rand(-20, 50),
                    'description' => $expense['description'],
                    'category' => $expense['category'],
                    'vendor' => $this->getVendor($expense['category']),
                    'date' => $this->randomDate($startDate, $endDate),
                    'is_business' => $expense['business'],
                    'recurring' => $expense['category'] === 'Utilities' || $expense['category'] === 'Software',
                    'tax_deductible' => $expense['business'],
                    'tax_category' => $expense['business'] ? strtolower($expense['category']) : 'personal',
                ]);
            }
        }
    }

    private function getVendor(string $category): string
    {
        $vendors = [
            'Software' => 'Adobe',
            'Equipment' => 'Apple Store',
            'Office' => 'IKEA',
            'Groceries' => 'Whole Foods',
            'Dining' => 'Local Restaurant',
            'Transportation' => 'Shell',
            'Entertainment' => 'Netflix',
            'Healthcare' => 'Medical Center',
            'Utilities' => 'PG&E',
        ];

        return $vendors[$category] ?? 'Generic Vendor';
    }

    private function randomDate(Carbon $start, Carbon $end): string
    {
        $timestamp = rand($start->timestamp, $end->timestamp);
        return Carbon::createFromTimestamp($timestamp)->format('Y-m-d');
    }
}
