<?php

namespace Tests\Feature;

use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncomeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_their_incomes()
    {
        Sanctum::actingAs($this->user);

        // Create incomes for this user
        Income::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        // Create income for another user (should not appear)
        Income::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->getJson('/api/incomes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'amount', 'description', 'category', 'date', 'user_id']
                ],
                'pagination'
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_income()
    {
        Sanctum::actingAs($this->user);

        $incomeData = [
            'amount' => 5000.00,
            'description' => 'Freelance project',
            'category' => 'Freelance',
            'date' => '2024-01-15',
            'is_business' => true,
            'recurring' => false,
        ];

        $response = $this->postJson('/api/incomes', $incomeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'amount', 'description', 'category', 'date', 'user_id']
            ]);

        $this->assertDatabaseHas('incomes', [
            'user_id' => $this->user->id,
            'amount' => 5000.00,
            'description' => 'Freelance project',
        ]);
    }

    public function test_user_can_update_their_income()
    {
        Sanctum::actingAs($this->user);

        $income = Income::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'amount' => 6000.00,
            'description' => 'Updated project payment',
        ];

        $response = $this->putJson("/api/incomes/{$income->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('incomes', [
            'id' => $income->id,
            'amount' => 6000.00,
            'description' => 'Updated project payment',
        ]);
    }

    public function test_user_cannot_update_other_users_income()
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $income = Income::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/incomes/{$income->id}", [
            'amount' => 6000.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_income()
    {
        Sanctum::actingAs($this->user);

        $income = Income::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/incomes/{$income->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
    }

    public function test_user_can_filter_incomes_by_date_range()
    {
        Sanctum::actingAs($this->user);

        Income::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2024-01-15',
            'amount' => 1000
        ]);

        Income::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2024-06-15',
            'amount' => 2000
        ]);

        $response = $this->getJson('/api/incomes?date_from=2024-01-01&date_to=2024-03-31');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_filter_incomes_by_category()
    {
        Sanctum::actingAs($this->user);

        Income::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Freelance'
        ]);

        Income::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Salary'
        ]);

        $response = $this->getJson('/api/incomes?category=Freelance');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_bulk_delete_incomes()
    {
        Sanctum::actingAs($this->user);

        $incomes = Income::factory()->count(3)->create(['user_id' => $this->user->id]);
        $ids = $incomes->pluck('id')->toArray();

        $response = $this->postJson('/api/incomes/bulk-delete', [
            'ids' => $ids
        ]);

        $response->assertStatus(200);
        
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('incomes', ['id' => $id]);
        }
    }

    public function test_user_can_get_income_stats()
    {
        Sanctum::actingAs($this->user);

        Income::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'amount' => 1000,
            'is_business' => true
        ]);

        Income::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'amount' => 500,
            'is_business' => false
        ]);

        $response = $this->getJson('/api/incomes/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_amount',
                    'count',
                    'average',
                    'business_income',
                    'personal_income',
                    'recurring_income'
                ]
            ]);
    }

    public function test_income_validation_works()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/incomes', [
            'amount' => 'invalid',
            'date' => 'invalid-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'date']);
    }

    public function test_unauthenticated_user_cannot_access_incomes()
    {
        $response = $this->getJson('/api/incomes');
        $response->assertStatus(401);
    }
}
