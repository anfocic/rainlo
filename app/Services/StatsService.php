<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /**
     * Get optimized expense stats with single query
     */
    public function getExpenseStats(int $userId, array $filters = []): array
    {
        $cacheKey = "expense_stats_{$userId}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($userId, $filters) {
            $query = Expense::forUser($userId);

            // Apply filters
            if (!empty($filters['date_from'])) {
                $query->where('date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('date', '<=', $filters['date_to']);
            }
            if (isset($filters['category'])) {
                $query->category($filters['category']);
            }
            if (isset($filters['is_business'])) {
                $query->isBusiness($filters['is_business']);
            }

            // Single optimized query for all stats (PostgreSQL compatible)
            $stats = $query->selectRaw('
                SUM(amount) as total_amount,
                COUNT(*) as count,
                AVG(amount) as average,
                SUM(CASE WHEN is_business = true THEN amount ELSE 0 END) as business_expenses,
                SUM(CASE WHEN is_business = false THEN amount ELSE 0 END) as personal_expenses,
                SUM(CASE WHEN recurring = true THEN amount ELSE 0 END) as recurring_expenses,
                SUM(CASE WHEN tax_deductible = true THEN amount ELSE 0 END) as tax_deductible_amount
            ')->first();

            // Get top categories in separate optimized query
            $topCategories = Expense::forUser($userId)
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            // Get top vendors in separate optimized query
            $topVendors = Expense::forUser($userId)
                ->selectRaw('vendor, SUM(amount) as total, COUNT(*) as count')
                ->whereNotNull('vendor')
                ->groupBy('vendor')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            return [
                'total_amount' => (float) $stats->total_amount ?: 0,
                'count' => (int) $stats->count,
                'average' => (float) $stats->average ?: 0,
                'business_expenses' => (float) $stats->business_expenses ?: 0,
                'personal_expenses' => (float) $stats->personal_expenses ?: 0,
                'recurring_expenses' => (float) $stats->recurring_expenses ?: 0,
                'tax_deductible_amount' => (float) $stats->tax_deductible_amount ?: 0,
                'top_categories' => $topCategories,
                'top_vendors' => $topVendors,
            ];
        });
    }

    /**
     * Get optimized income stats with single query
     */
    public function getIncomeStats(int $userId, array $filters = []): array
    {
        $cacheKey = "income_stats_{$userId}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($userId, $filters) {
            $query = Income::forUser($userId);

            // Apply filters
            if (!empty($filters['date_from'])) {
                $query->where('date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('date', '<=', $filters['date_to']);
            }
            if (isset($filters['category'])) {
                $query->category($filters['category']);
            }
            if (isset($filters['is_business'])) {
                $query->isBusiness($filters['is_business']);
            }

            // Single optimized query for all stats (PostgreSQL compatible)
            $stats = $query->selectRaw('
                SUM(amount) as total_amount,
                COUNT(*) as count,
                AVG(amount) as average,
                SUM(CASE WHEN is_business = true THEN amount ELSE 0 END) as business_income,
                SUM(CASE WHEN is_business = false THEN amount ELSE 0 END) as personal_income,
                SUM(CASE WHEN recurring = true THEN amount ELSE 0 END) as recurring_income
            ')->first();

            // Get top categories in separate optimized query
            $topCategories = Income::forUser($userId)
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            // Get top sources in separate optimized query
            $topSources = Income::forUser($userId)
                ->selectRaw('source, SUM(amount) as total, COUNT(*) as count')
                ->whereNotNull('source')
                ->groupBy('source')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            return [
                'total_amount' => (float) $stats->total_amount ?: 0,
                'count' => (int) $stats->count,
                'average' => (float) $stats->average ?: 0,
                'business_income' => (float) $stats->business_income ?: 0,
                'personal_income' => (float) $stats->personal_income ?: 0,
                'recurring_income' => (float) $stats->recurring_income ?: 0,
                'top_categories' => $topCategories,
                'top_sources' => $topSources,
            ];
        });
    }

    /**
     * Clear stats cache for user
     */
    public function clearStatsCache(int $userId): void
    {
        $patterns = [
            "expense_stats_{$userId}_*",
            "income_stats_{$userId}_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
