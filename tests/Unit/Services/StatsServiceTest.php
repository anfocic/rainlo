<?php

namespace Tests\Unit\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private StatsService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatsService();
        $this->user = User::factory()->create();
    }

    public function test_get_transaction_stats_returns_correct_structure()
    {
        // Create test transactions
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 1000
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 500
        ]);

        $stats = $this->service->getTransactionStats($this->user->id);

        $this->assertArrayHasKey('income', $stats);
        $this->assertArrayHasKey('expense', $stats);
        $this->assertArrayHasKey('net_total', $stats);

        $this->assertEquals(1000, $stats['income']['total']);
        $this->assertEquals(1, $stats['income']['count']);
        $this->assertEquals(1000, $stats['income']['average']);

        $this->assertEquals(500, $stats['expense']['total']);
        $this->assertEquals(1, $stats['expense']['count']);
        $this->assertEquals(500, $stats['expense']['average']);

        $this->assertEquals(500, $stats['net_total']);
    }

    public function test_get_transaction_stats_with_filters()
    {
        // Create transactions with different categories
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 1000,
            'category' => 'salary'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 500,
            'category' => 'freelance'
        ]);

        $stats = $this->service->getTransactionStats($this->user->id, ['category' => 'salary']);

        $this->assertEquals(1000, $stats['income']['total']);
        $this->assertEquals(1, $stats['income']['count']);
    }

    public function test_get_dashboard_summary_returns_correct_data()
    {
        // Create test transactions
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 2000,
            'is_business' => true
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 800,
            'is_business' => false
        ]);

        $summary = $this->service->getDashboardSummary($this->user->id);

        $this->assertArrayHasKey('total_income', $summary);
        $this->assertArrayHasKey('total_expenses', $summary);
        $this->assertArrayHasKey('net_income', $summary);
        $this->assertArrayHasKey('business_income', $summary);
        $this->assertArrayHasKey('business_expenses', $summary);

        $this->assertEquals(2000, $summary['total_income']);
        $this->assertEquals(800, $summary['total_expenses']);
        $this->assertEquals(1200, $summary['net_income']);
        $this->assertEquals(2000, $summary['business_income']);
        $this->assertEquals(0, $summary['business_expenses']);
    }

    public function test_get_recent_transactions_returns_limited_results()
    {
        // Create multiple transactions
        for ($i = 0; $i < 15; $i++) {
            Transaction::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'income',
                'amount' => 100 * ($i + 1),
                'date' => now()->subDays($i)
            ]);
        }

        $recent = $this->service->getRecentTransactions($this->user->id, 5);

        $this->assertCount(5, $recent);
        
        // Should be ordered by date descending (most recent first)
        $this->assertEquals(100, $recent[0]['amount']); // Most recent (today)
        $this->assertEquals(200, $recent[1]['amount']); // Yesterday
    }

    public function test_caching_works_correctly()
    {
        // Clear cache first
        Cache::flush();

        // Create a transaction
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 1000
        ]);

        // First call should hit database and cache result
        $stats1 = $this->service->getTransactionStats($this->user->id);
        
        // Create another transaction (should not affect cached result)
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 500
        ]);

        // Second call should return cached result (still showing only first transaction)
        $stats2 = $this->service->getTransactionStats($this->user->id);
        
        $this->assertEquals($stats1, $stats2);
        $this->assertEquals(1000, $stats2['income']['total']); // Should still be 1000, not 1500
    }

    public function test_clear_stats_cache_removes_cached_data()
    {
        // Create transaction and cache stats
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 1000
        ]);

        $this->service->getTransactionStats($this->user->id);
        
        // Clear cache
        $this->service->clearStatsCache($this->user->id);
        
        // Create another transaction
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 500
        ]);

        // Should now reflect both transactions (cache was cleared)
        $stats = $this->service->getTransactionStats($this->user->id);
        $this->assertEquals(1500, $stats['income']['total']);
    }

    public function test_stats_are_user_specific()
    {
        $otherUser = User::factory()->create();

        // Create transactions for different users
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 1000
        ]);

        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'type' => 'income',
            'amount' => 2000
        ]);

        $userStats = $this->service->getTransactionStats($this->user->id);
        $otherUserStats = $this->service->getTransactionStats($otherUser->id);

        $this->assertEquals(1000, $userStats['income']['total']);
        $this->assertEquals(2000, $otherUserStats['income']['total']);
    }
}
