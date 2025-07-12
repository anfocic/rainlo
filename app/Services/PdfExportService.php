<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PdfExportService
{
    /**
     * Export expenses to PDF
     */
    public function exportExpenses(
        User $user,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $category = null,
        ?bool $isBusiness = null
    ): string {
        $query = Expense::forUser($user->id)
            ->orderBy('date', 'desc');

        // Apply filters
        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($isBusiness !== null) {
            $query->where('is_business', $isBusiness);
        }

        $expenses = $query->get();
        $totalAmount = $expenses->sum('amount');
        $businessExpenses = $expenses->where('is_business', true)->sum('amount');
        $personalExpenses = $expenses->where('is_business', false)->sum('amount');

        $data = [
            'user' => $user,
            'expenses' => $expenses,
            'summary' => [
                'total_amount' => $totalAmount,
                'business_expenses' => $businessExpenses,
                'personal_expenses' => $personalExpenses,
                'total_count' => $expenses->count(),
                'business_count' => $expenses->where('is_business', true)->count(),
                'personal_count' => $expenses->where('is_business', false)->count(),
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'category' => $category,
                'is_business' => $isBusiness,
            ],
            'generated_at' => now(),
            'period' => $this->getPeriodDescription($dateFrom, $dateTo),
        ];

        $pdf = Pdf::loadView('pdf.expenses', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Export incomes to PDF
     */
    public function exportIncomes(
        User $user,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $category = null,
        ?bool $isBusiness = null
    ): string {
        $query = Income::forUser($user->id)
            ->orderBy('date', 'desc');

        // Apply filters
        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($isBusiness !== null) {
            $query->where('is_business', $isBusiness);
        }

        $incomes = $query->get();
        $totalAmount = $incomes->sum('amount');
        $businessIncomes = $incomes->where('is_business', true)->sum('amount');
        $personalIncomes = $incomes->where('is_business', false)->sum('amount');

        $data = [
            'user' => $user,
            'incomes' => $incomes,
            'summary' => [
                'total_amount' => $totalAmount,
                'business_incomes' => $businessIncomes,
                'personal_incomes' => $personalIncomes,
                'total_count' => $incomes->count(),
                'business_count' => $incomes->where('is_business', true)->count(),
                'personal_count' => $incomes->where('is_business', false)->count(),
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'category' => $category,
                'is_business' => $isBusiness,
            ],
            'generated_at' => now(),
            'period' => $this->getPeriodDescription($dateFrom, $dateTo),
        ];

        $pdf = Pdf::loadView('pdf.incomes', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Export financial summary to PDF
     */
    public function exportFinancialSummary(
        User $user,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): string {
        $expenseQuery = Expense::forUser($user->id);
        $incomeQuery = Income::forUser($user->id);

        // Apply date filters
        if ($dateFrom) {
            $expenseQuery->where('date', '>=', $dateFrom);
            $incomeQuery->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $expenseQuery->where('date', '<=', $dateTo);
            $incomeQuery->where('date', '<=', $dateTo);
        }

        $expenses = $expenseQuery->get();
        $incomes = $incomeQuery->get();

        $totalExpenses = $expenses->sum('amount');
        $totalIncomes = $incomes->sum('amount');
        $netIncome = $totalIncomes - $totalExpenses;

        $businessExpenses = $expenses->where('is_business', true)->sum('amount');
        $personalExpenses = $expenses->where('is_business', false)->sum('amount');
        $businessIncomes = $incomes->where('is_business', true)->sum('amount');
        $personalIncomes = $incomes->where('is_business', false)->sum('amount');

        // Category breakdowns
        $expensesByCategory = $expenses->groupBy('category')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
                'business' => $items->where('is_business', true)->sum('amount'),
                'personal' => $items->where('is_business', false)->sum('amount'),
            ];
        });

        $incomesByCategory = $incomes->groupBy('category')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
                'business' => $items->where('is_business', true)->sum('amount'),
                'personal' => $items->where('is_business', false)->sum('amount'),
            ];
        });

        $data = [
            'user' => $user,
            'summary' => [
                'total_incomes' => $totalIncomes,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'business_incomes' => $businessIncomes,
                'personal_incomes' => $personalIncomes,
                'business_expenses' => $businessExpenses,
                'personal_expenses' => $personalExpenses,
                'business_net' => $businessIncomes - $businessExpenses,
                'personal_net' => $personalIncomes - $personalExpenses,
            ],
            'categories' => [
                'expenses' => $expensesByCategory,
                'incomes' => $incomesByCategory,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'generated_at' => now(),
            'period' => $this->getPeriodDescription($dateFrom, $dateTo),
        ];

        $pdf = Pdf::loadView('pdf.financial-summary', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Export tax calculation to PDF
     */
    public function exportTaxCalculation(array $taxCalculation, array $metadata = []): string
    {
        $data = [
            'calculation' => $taxCalculation,
            'metadata' => $metadata,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('pdf.tax-calculation', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Get period description for display
     */
    private function getPeriodDescription(?string $dateFrom, ?string $dateTo): string
    {
        if (!$dateFrom && !$dateTo) {
            return 'All Time';
        }

        try {
            if ($dateFrom && !$dateTo) {
                return 'From ' . Carbon::parse($dateFrom)->format('M j, Y');
            }

            if (!$dateFrom && $dateTo) {
                return 'Until ' . Carbon::parse($dateTo)->format('M j, Y');
            }

            $from = Carbon::parse($dateFrom);
            $to = Carbon::parse($dateTo);

            if ($from->isSameDay($to)) {
                return $from->format('M j, Y');
            }

            return $from->format('M j, Y') . ' - ' . $to->format('M j, Y');
        } catch (\Exception $e) {
            return 'Invalid Date Range';
        }
    }
}
