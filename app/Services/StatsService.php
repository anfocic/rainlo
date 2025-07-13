<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

class StatsService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function clearStatsCache(int $userId): void
    {
        // Clear specific cache keys that we know exist
        $cacheKeys = [
            "transaction_stats_{$userId}_" . md5(serialize([])), // Empty filters
            "dashboard_summary_{$userId}",
            "income_stats_{$userId}",
            "expense_stats_{$userId}",
        ];

        // Also clear recent transactions with common limits
        for ($limit = 5; $limit <= 20; $limit += 5) {
            $cacheKeys[] = "recent_transactions_{$userId}_{$limit}";
        }

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    public function getTransactionStats(int $userId, array $filters = []): array
    {
        $cacheKey = "transaction_stats_{$userId}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $filters) {
            $query = Transaction::forUser($userId);

            // Apply filters
            if (isset($filters['date_from']) || isset($filters['date_to'])) {
                $query->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null);
            }
            if (isset($filters['category'])) {
                $query->category($filters['category']);
            }
            if (isset($filters['is_business'])) {
                $query->isBusiness($filters['is_business']);
            }
            if (isset($filters['recurring'])) {
                $query->recurring($filters['recurring']);
            }

            $incomeTotal = (clone $query)->where('type', 'income')->sum('amount');
            $incomeCount = (clone $query)->where('type', 'income')->count();
            $expenseTotal = (clone $query)->where('type', 'expense')->sum('amount');
            $expenseCount = (clone $query)->where('type', 'expense')->count();

            return [
                'income' => [
                    'total' => $incomeTotal,
                    'count' => $incomeCount,
                    'average' => $incomeCount > 0 ? $incomeTotal / $incomeCount : 0,
                ],
                'expense' => [
                    'total' => $expenseTotal,
                    'count' => $expenseCount,
                    'average' => $expenseCount > 0 ? $expenseTotal / $expenseCount : 0,
                ],
                'net_total' => $incomeTotal - $expenseTotal,
            ];
        });
    }

    public function getDashboardSummary(int $userId): array
    {
        $cacheKey = "dashboard_summary_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $totalIncome = Transaction::forUser($userId)->where('type', 'income')->sum('amount');
            $totalExpenses = Transaction::forUser($userId)->where('type', 'expense')->sum('amount');

            return [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_income' => $totalIncome - $totalExpenses,
                'income_count' => Transaction::forUser($userId)->where('type', 'income')->count(),
                'expense_count' => Transaction::forUser($userId)->where('type', 'expense')->count(),
                'business_income' => Transaction::forUser($userId)->where('type', 'income')->where('is_business', true)->sum('amount'),
                'business_expenses' => Transaction::forUser($userId)->where('type', 'expense')->where('is_business', true)->sum('amount'),
            ];
        });
    }

    public function getRecentTransactions(int $userId, int $limit = 10): array
    {
        $cacheKey = "recent_transactions_{$userId}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $limit) {
            return Transaction::forUser($userId)
                ->latest('date')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }
}
