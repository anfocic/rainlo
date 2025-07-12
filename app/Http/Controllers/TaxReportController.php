<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxReportController extends Controller
{
    public function annual(Request $request, int $year): JsonResponse
    {
        $request->validate([
            'include_personal' => 'boolean',
        ]);

        $includePersonal = $request->boolean('include_personal', false);
        $userId = auth()->id();

        // Date range for the tax year
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        // Base queries
        $incomeQuery = Income::forUser($userId)
            ->whereBetween('date', [$startDate, $endDate]);

        $expenseQuery = Expense::forUser($userId)
            ->whereBetween('date', [$startDate, $endDate]);

        // Filter by business only if not including personal
        if (!$includePersonal) {
            $incomeQuery->where('is_business', true);
            $expenseQuery->where('is_business', true);
        }

        // Income breakdown
        $incomeData = [
            'total_income' => $incomeQuery->sum('amount'),
            'business_income' => Income::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('is_business', true)
                ->sum('amount'),
            'personal_income' => Income::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('is_business', false)
                ->sum('amount'),
            'by_category' => Income::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('category, SUM(amount) as total, is_business')
                ->groupBy('category', 'is_business')
                ->orderByDesc('total')
                ->get(),
            'by_month' => Income::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('MONTH(date) as month, SUM(amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ];

        // Expense breakdown
        $expenseData = [
            'total_expenses' => $expenseQuery->sum('amount'),
            'tax_deductible_expenses' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('tax_deductible', true)
                ->sum('amount'),
            'business_expenses' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('is_business', true)
                ->sum('amount'),
            'personal_expenses' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('is_business', false)
                ->sum('amount'),
            'by_category' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('category, SUM(amount) as total, tax_deductible, is_business')
                ->groupBy('category', 'tax_deductible', 'is_business')
                ->orderByDesc('total')
                ->get(),
            'by_tax_category' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotNull('tax_category')
                ->selectRaw('tax_category, SUM(amount) as total')
                ->groupBy('tax_category')
                ->orderByDesc('total')
                ->get(),
            'by_month' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('MONTH(date) as month, SUM(amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ];

        // Summary calculations
        $totalIncome = $incomeData['total_income'];
        $totalExpenses = $expenseData['total_expenses'];
        $netIncome = $totalIncome - $totalExpenses;
        $taxDeductibleExpenses = $expenseData['tax_deductible_expenses'];

        return response()->json([
            'data' => [
                'year' => $year,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'summary' => [
                    'total_income' => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'net_income' => $netIncome,
                    'tax_deductible_expenses' => $taxDeductibleExpenses,
                    'estimated_tax_savings' => $taxDeductibleExpenses * 0.25, // Rough estimate
                ],
                'income' => $incomeData,
                'expenses' => $expenseData,
                'missing_receipts' => Expense::forUser($userId)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->where('tax_deductible', true)
                    ->whereNull('receipt_url')
                    ->count(),
            ]
        ]);
    }

    public function quarterly(Request $request, int $year, int $quarter): JsonResponse
    {
        if ($quarter < 1 || $quarter > 4) {
            return response()->json(['message' => 'Quarter must be between 1 and 4'], 400);
        }

        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $startDate = Carbon::create($year, $startMonth, 1);
        $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth();

        $userId = auth()->id();

        $summary = [
            'quarter' => $quarter,
            'year' => $year,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'income' => Income::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount'),
            'expenses' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount'),
            'tax_deductible_expenses' => Expense::forUser($userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('tax_deductible', true)
                ->sum('amount'),
        ];

        $summary['net_income'] = $summary['income'] - $summary['expenses'];

        return response()->json(['data' => $summary]);
    }

    /**
     * Get tax deductions summary
     */
    public function deductions(Request $request, int $year): JsonResponse
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        $userId = auth()->id();

        $deductions = Expense::forUser($userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('tax_deductible', true)
            ->selectRaw('
                tax_category,
                category,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN receipt_url IS NOT NULL THEN 1 ELSE 0 END) as receipts_count
            ')
            ->groupBy('tax_category', 'category')
            ->orderByDesc('total_amount')
            ->get();

        $summary = [
            'total_deductions' => $deductions->sum('total_amount'),
            'total_transactions' => $deductions->sum('transaction_count'),
            'receipts_available' => $deductions->sum('receipts_count'),
            'receipts_missing' => $deductions->sum('transaction_count') - $deductions->sum('receipts_count'),
        ];

        return response()->json([
            'data' => [
                'year' => $year,
                'summary' => $summary,
                'deductions' => $deductions,
                'estimated_tax_savings' => $summary['total_deductions'] * 0.25, // Rough estimate
            ]
        ]);
    }

    /**
     * Get tax categories with predefined options
     */
    public function categories(): JsonResponse
    {
        $categories = [
            'business_expenses' => [
                'office_supplies' => 'Office Supplies',
                'software' => 'Software & Subscriptions',
                'equipment' => 'Equipment & Hardware',
                'travel' => 'Business Travel',
                'meals' => 'Business Meals (50% deductible)',
                'marketing' => 'Marketing & Advertising',
                'professional_services' => 'Professional Services',
                'training' => 'Training & Education',
                'utilities' => 'Business Utilities',
                'rent' => 'Office Rent',
            ],
            'income_types' => [
                'salary' => 'Salary/Wages',
                'freelance' => 'Freelance Income',
                'business' => 'Business Income',
                'investment' => 'Investment Income',
                'rental' => 'Rental Income',
                'other' => 'Other Income',
            ]
        ];

        return response()->json(['data' => $categories]);
    }
}
