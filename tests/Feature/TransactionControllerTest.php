<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_an_income_transaction()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transactions', [
                'type' => 'income',
                'amount' => 5000.00,
                'description' => 'Salary payment',
                'category' => 'Salary',
                'date' => '2025-01-15',
                'is_business' => false,
                'source' => 'Company ABC'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'type', 'amount', 'description', 'category',
                    'date', 'is_business', 'source', 'user_id'
                ],
                'timestamp'
            ]);

        $this->assertDatabaseHas('transactions', [
            'type' => 'income',
            'amount' => 5000.00,
            'description' => 'Salary payment',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_create_an_expense_transaction()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transactions', [
                'type' => 'expense',
                'amount' => 150.00,
                'description' => 'Office supplies',
                'category' => 'Business',
                'date' => '2025-01-15',
                'is_business' => true,
                'vendor' => 'Office Depot'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'type', 'amount', 'description', 'category',
                    'date', 'is_business', 'vendor', 'user_id'
                ],
                'timestamp'
            ]);

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'amount' => 150.00,
            'description' => 'Office supplies',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_filter_transactions_by_type()
    {
        // Create test data
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

        // Test income filter
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions?type=income');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('income', $data[0]['type']);

        // Test expense filter
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions?type=expense');

        $response->assertStatus(200);
        $data = $response->json('data'); // Fixed: should be 'data' not 'data.data'
        $this->assertCount(1, $data);
        $this->assertEquals('expense', $data[0]['type']);
    }

    /** @test */
    public function it_validates_transaction_creation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transactions', [
                'type' => 'invalid',
                'amount' => -100,
                'description' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'amount', 'description', 'date']);
    }

    /** @test */
    public function it_can_get_transaction_stats()
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

        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'income',
                    'expense',
                    'net_total'
                ],
                'timestamp'
            ]);
    }

    /** @test */
    public function it_can_update_a_transaction()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'amount' => 1000,
            'description' => 'Original description'
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/transactions/{$transaction->id}", [
                'type' => 'income',
                'amount' => 1500,
                'description' => 'Updated description',
                'date' => $transaction->date->format('Y-m-d')
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 1500,
            'description' => 'Updated description'
        ]);
    }

    /** @test */
    public function it_can_delete_a_transaction()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_other_users_transactions()
    {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_bulk_delete_transactions()
    {
        // Create multiple transactions for the user
        $transactions = Transaction::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        // Create a transaction for another user (should not be deleted)
        $otherUserTransaction = Transaction::factory()->create([
            'user_id' => User::factory()->create()->id
        ]);

        $transactionIds = $transactions->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson('/api/transactions/bulk-delete', [
                'ids' => $transactionIds
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['deleted_count'],
                'timestamp'
            ]);

        $this->assertEquals(3, $response->json('data.deleted_count'));

        // Verify transactions are deleted
        foreach ($transactions as $transaction) {
            $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        }

        // Verify other user's transaction is not deleted
        $this->assertDatabaseHas('transactions', ['id' => $otherUserTransaction->id]);
    }

    /** @test */
    public function it_validates_bulk_delete_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transactions/bulk-delete', [
                'ids' => []
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);

        // Test with too many IDs
        $response = $this->actingAs($this->user)
            ->postJson('/api/transactions/bulk-delete', [
                'ids' => range(1, 101) // More than 100
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function it_can_filter_transactions_by_date_range()
    {
        // Create transactions with different dates
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-01-01',
            'description' => 'January transaction'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-02-15',
            'description' => 'February transaction'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-03-30',
            'description' => 'March transaction'
        ]);

        // Filter by date range
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions?date_from=2025-02-01&date_to=2025-02-28');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('February transaction', $data[0]['description']);
    }

    /** @test */
    public function it_can_filter_transactions_by_amount_range()
    {
        // Create transactions with different amounts
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100.00
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500.00
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00
        ]);

        // Filter by amount range
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions?min=200&max=800');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(500.00, $data[0]['amount']);
    }

    /** @test */
    public function it_can_filter_transactions_by_category()
    {
        // Create transactions with different categories
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Transport'
        ]);

        // Filter by category
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions?category=Food');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Food', $data[0]['category']);
    }

    /** @test */
    public function it_can_filter_transactions_by_business_flag()
    {
        // Create business and personal transactions
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'is_business' => true
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'is_business' => false
        ]);

        // Filter by business flag
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions?is_business=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_business']);
    }
}
