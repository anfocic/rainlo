<?php

namespace Tests;

use App\Models\User;
use App\Services\TaxCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TaxCalculatorService $taxCalculatorService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->taxCalculatorService = new TaxCalculatorService();
    }

    /** @test */
    public function it_can_calculate_tax_for_single_person_basic_rate()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/calculate', [
                'annual_income' => 30000,
                'marital_status' => 'single',
                'has_children' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'annual' => [
                        'annual_income',
                        'marital_status',
                        'has_children',
                        'breakdown' => [
                            'income_tax',
                            'usc',
                            'prsi',
                            'gross_tax',
                            'tax_credits',
                            'net_tax',
                        ],
                        'net_income',
                        'effective_tax_rate',
                        'marginal_tax_rate',
                    ],
                    'monthly',
                ],
                'meta' => [
                    'calculation_date',
                    'tax_year',
                ],
                'timestamp',
            ]);

        $data = $response->json('data.annual');

        // Verify basic calculations for €30,000 single person
        $this->assertEquals(30000, $data['annual_income']);
        $this->assertEquals('single', $data['marital_status']);
        $this->assertFalse($data['has_children']);

        // Income tax: €30,000 @ 20% = €6,000
        $this->assertEquals(6000, $data['breakdown']['income_tax']);

        // PRSI: €30,000 @ 4.2% = €1,260
        $this->assertEquals(1260, $data['breakdown']['prsi']);

        // Tax credits: Single (€2,000) + Employee PAYE (€2,000) = €4,000
        $this->assertEquals(4000, $data['breakdown']['tax_credits']);
    }

    /** @test */
    public function it_can_calculate_tax_for_single_person_higher_rate()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/calculate', [
                'annual_income' => 60000,
                'marital_status' => 'single',
                'has_children' => false,
            ]);

        $response->assertStatus(200);
        $data = $response->json('data.annual');

        // Income tax: €44,000 @ 20% + €16,000 @ 40% = €8,800 + €6,400 = €15,200
        $this->assertEquals(15200, $data['breakdown']['income_tax']);

        // Should be in higher rate band
        // Income Tax: 40%, USC: 3% (third band), PRSI: 4.2% = 47.2%
        $this->assertEquals(47.2, $data['marginal_tax_rate']);
    }

    /** @test */
    public function it_can_calculate_tax_for_married_couple_one_income()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/calculate', [
                'annual_income' => 60000,
                'marital_status' => 'married',
                'spouse_income' => 0,
                'has_children' => false,
            ]);

        $response->assertStatus(200);
        $data = $response->json('data.annual');

        // Income tax: €53,000 @ 20% + €7,000 @ 40% = €10,600 + €2,800 = €13,400
        $this->assertEquals(13400, $data['breakdown']['income_tax']);

        // Tax credits: Married (€4,000) + Employee PAYE (€2,000) = €6,000
        $this->assertEquals(6000, $data['breakdown']['tax_credits']);
    }

    /** @test */
    public function it_can_calculate_tax_for_married_couple_two_incomes()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/calculate', [
                'annual_income' => 60000,
                'marital_status' => 'married',
                'spouse_income' => 25000,
                'has_children' => false,
            ]);

        $response->assertStatus(200);
        $data = $response->json('data.annual');

        // Standard rate band: €53,000 + min(€35,000, €25,000) = €78,000
        // Income tax: €60,000 @ 20% = €12,000 (all in standard rate)
        $this->assertEquals(12000, $data['breakdown']['income_tax']);
    }

    /** @test */
    public function it_can_calculate_usc_correctly()
    {
        // Test USC calculation for €50,000 income
        $calculation = $this->taxCalculatorService->calculateTax(50000, 'single');

        // USC bands for €50,000:
        // €12,012 @ 0.5% = €60.06
        // €15,370 @ 2% = €307.40
        // €22,618 @ 3% = €678.54 (€50,000 - €27,382)
        // Total USC = €1,046

        $expectedUSC = (12012 * 0.005) + (15370 * 0.02) + (22618 * 0.03);
        $this->assertEquals(round($expectedUSC, 2), $calculation['breakdown']['usc']);
    }

    /** @test */
    public function it_can_get_tax_rates_and_bands()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/tax/rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'income_tax' => [
                        'rates',
                        'bands',
                    ],
                    'usc' => [
                        'bands',
                    ],
                    'prsi' => [
                        'rate',
                    ],
                    'tax_credits',
                    'year',
                ],
                'meta' => [
                    'last_updated',
                    'source',
                ],
                'timestamp',
            ]);

        $data = $response->json('data');
        $this->assertEquals(2025, $data['year']);
        $this->assertEquals(0.042, $data['prsi']['rate']);
        $this->assertEquals(44000, $data['income_tax']['bands']['single']);
    }

    /** @test */
    public function it_can_compare_multiple_scenarios()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/compare', [
                'scenarios' => [
                    [
                        'annual_income' => 30000,
                        'marital_status' => 'single',
                        'has_children' => false,
                        'label' => 'Single €30k',
                    ],
                    [
                        'annual_income' => 60000,
                        'marital_status' => 'married',
                        'spouse_income' => 0,
                        'has_children' => false,
                        'label' => 'Married €60k',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'scenarios' => [
                        '*' => [
                            'scenario_id',
                            'label',
                            'calculation',
                        ],
                    ],
                    'comparison_summary' => [
                        'highest_income',
                        'highest_net_income',
                        'lowest_effective_rate',
                        'highest_effective_rate',
                    ],
                ],
            ]);

        $scenarios = $response->json('data.scenarios');
        $this->assertCount(2, $scenarios);
        $this->assertEquals('Single €30k', $scenarios[0]['label']);
        $this->assertEquals('Married €60k', $scenarios[1]['label']);
    }

    /** @test */
    public function it_validates_tax_calculation_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/calculate', [
                'annual_income' => -1000, // Invalid negative income
                'marital_status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['annual_income', 'marital_status']);
    }

    /** @test */
    public function it_requires_spouse_income_for_married_status()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/calculate', [
                'annual_income' => 50000,
                'marital_status' => 'married',
                // Missing spouse_income
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['spouse_income']);
    }

    /** @test */
    public function it_can_calculate_marginal_tax_rate()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/marginal-rate', [
                'annual_income' => 45000,
                'marital_status' => 'single',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'annual_income',
                    'marginal_tax_rate',
                    'effective_marginal_rate',
                    'tax_on_next_1000',
                    'net_from_next_1000',
                ],
            ]);

        $data = $response->json('data');

        // At €45,000 (above €44,000 threshold), marginal rate should be higher rate
        // Income Tax: 40%, USC: 3% (third band), PRSI: 4.2% = 47.2%
        $this->assertEquals(47.2, $data['marginal_tax_rate']);
    }



    /** @test */
    public function it_requires_authentication_for_tax_calculations()
    {
        $response = $this->postJson('/api/tax/calculate', [
            'annual_income' => 30000,
            'marital_status' => 'single',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_includes_monthly_breakdown_in_calculation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tax/calculate', [
                'annual_income' => 36000, // €3,000 per month
                'marital_status' => 'single',
                'has_children' => false,
            ]);

        $response->assertStatus(200);

        $monthly = $response->json('data.monthly');
        $this->assertEquals(3000, $monthly['monthly_gross_income']);
        $this->assertArrayHasKey('monthly_breakdown', $monthly);
        $this->assertArrayHasKey('monthly_net_income', $monthly);
    }
}
