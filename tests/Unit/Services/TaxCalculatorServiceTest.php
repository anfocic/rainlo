<?php

namespace Tests\Unit\Services;

use App\Domain\Tax\Contracts\TaxCalculatorInterface;
use Tests\TestCase;

class TaxCalculatorServiceTest extends TestCase
{
    private TaxCalculatorInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxCalculatorInterface::class);
    }

    public function test_calculate_tax_for_single_person_low_income()
    {
        $result = $this->service->calculateTax(30000, 'single');

        $this->assertEquals(30000, $result['annual_income']);
        $this->assertEquals('single', $result['marital_status']);
        $this->assertFalse($result['has_children']);
        $this->assertNull($result['spouse_income']);

        // Should be in standard rate band
        $this->assertLessThan(30000 * 0.4, $result['breakdown']['income_tax']);
        $this->assertGreaterThan(0, $result['breakdown']['usc']);
        $this->assertGreaterThan(0, $result['breakdown']['prsi']);
        $this->assertGreaterThan(0, $result['net_income']);
    }

    public function test_calculate_tax_for_single_person_high_income()
    {
        $result = $this->service->calculateTax(80000, 'single');

        $this->assertEquals(80000, $result['annual_income']);

        // Should have higher rate tax applied
        $expectedStandardRateTax = 44000 * 0.20; // €8,800
        $expectedHigherRateTax = (80000 - 44000) * 0.40; // €14,400
        $expectedTotalIncomeTax = $expectedStandardRateTax + $expectedHigherRateTax;

        $this->assertEquals($expectedTotalIncomeTax, $result['breakdown']['income_tax']);
    }

    public function test_calculate_tax_at_band_boundaries()
    {
        // Test exactly at single person band limit
        $result = $this->service->calculateTax(44000, 'single');

        $this->assertEquals(44000, $result['annual_income']);
        $this->assertEquals(44000 * 0.20, $result['breakdown']['income_tax']);
    }

    public function test_calculate_tax_for_married_couple()
    {
        $result = $this->service->calculateTax(60000, 'married');

        $this->assertEquals(60000, $result['annual_income']);
        $this->assertEquals('married', $result['marital_status']);

        // Married person should get higher tax credits: married (4000) + paye (2000) = 6000
        $this->assertEquals(6000, $result['breakdown']['tax_credits']);
    }

    public function test_calculate_tax_for_single_parent()
    {
        $result = $this->service->calculateTax(40000, 'single', true);

        $this->assertEquals(40000, $result['annual_income']);
        $this->assertTrue($result['has_children']);

        // Should include single parent child carer credit
        $expectedCredits = 2000 + 2000 + 1900; // single + paye + child carer
        $this->assertEquals($expectedCredits, $result['breakdown']['tax_credits']);
    }

    public function test_marginal_tax_rate_calculation()
    {
        $result = $this->service->calculateTax(30000, 'single');

        // At €30k, should be in standard rate (20%) + USC 3% (third band) + PRSI 4.2% = 27.2%
        $this->assertEquals(27.2, $result['marginal_tax_rate']);
    }

    public function test_monthly_breakdown_calculation()
    {
        $annualCalculation = $this->service->calculateTax(50000, 'single');
        $monthlyBreakdown = $this->service->calculateMonthlyBreakdown($annualCalculation);

        $this->assertArrayHasKey('monthly_gross_income', $monthlyBreakdown);
        $this->assertArrayHasKey('monthly_net_income', $monthlyBreakdown);
        $this->assertArrayHasKey('monthly_breakdown', $monthlyBreakdown);

        // Monthly should be annual divided by 12
        $this->assertEquals(round(50000 / 12, 2), $monthlyBreakdown['monthly_gross_income']);
        $this->assertEquals(round($annualCalculation['net_income'] / 12, 2), $monthlyBreakdown['monthly_net_income']);
    }

    public function test_zero_income_scenario()
    {
        $result = $this->service->calculateTax(0, 'single');

        $this->assertEquals(0, $result['annual_income']);
        $this->assertEquals(0, $result['breakdown']['income_tax']);
        $this->assertEquals(0, $result['breakdown']['usc']);
        $this->assertEquals(0, $result['breakdown']['prsi']);
        $this->assertEquals(0, $result['net_income']);
    }

    public function test_very_high_income_scenario()
    {
        $result = $this->service->calculateTax(500000, 'single');

        $this->assertEquals(500000, $result['annual_income']);
        $this->assertGreaterThan(100000, $result['breakdown']['net_tax']);
        $this->assertLessThan(500000, $result['net_income']);
        $this->assertGreaterThan(30, $result['effective_tax_rate']);
    }
}
