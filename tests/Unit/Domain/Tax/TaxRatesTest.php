<?php

namespace Tests\Unit\Domain\Tax;

use App\Domain\Tax\TaxRates;
use PHPUnit\Framework\TestCase;

class TaxRatesTest extends TestCase
{
    public function test_income_tax_rates_are_correct()
    {
        $this->assertEquals(0.20, TaxRates::INCOME_TAX_RATES['standard_rate']);
        $this->assertEquals(0.40, TaxRates::INCOME_TAX_RATES['higher_rate']);
    }

    public function test_income_tax_bands_are_correct()
    {
        $this->assertEquals(44000, TaxRates::INCOME_TAX_BANDS['single']);
        $this->assertEquals(53000, TaxRates::INCOME_TAX_BANDS['married_one_income']);
        $this->assertEquals(48000, TaxRates::INCOME_TAX_BANDS['single_parent']);
    }

    public function test_usc_bands_are_correct()
    {
        $expectedBands = [
            ['limit' => 12012, 'rate' => 0.005],
            ['limit' => 27382, 'rate' => 0.02],
            ['limit' => 70044, 'rate' => 0.03],
            ['limit' => null, 'rate' => 0.08],
        ];

        $this->assertEquals($expectedBands, TaxRates::USC_BANDS);
    }

    public function test_prsi_rate_is_correct()
    {
        $this->assertEquals(0.042, TaxRates::PRSI_RATE);
    }

    public function test_tax_credits_are_correct()
    {
        $this->assertEquals(2000, TaxRates::TAX_CREDITS['single_person']);
        $this->assertEquals(4000, TaxRates::TAX_CREDITS['married_person']);
        $this->assertEquals(2000, TaxRates::TAX_CREDITS['employee_paye']);
        $this->assertEquals(1900, TaxRates::TAX_CREDITS['single_parent_child_carer']);
    }

    public function test_as_array_returns_complete_structure()
    {
        $result = TaxRates::asArray();

        $this->assertArrayHasKey('income_tax', $result);
        $this->assertArrayHasKey('usc', $result);
        $this->assertArrayHasKey('prsi', $result);
        $this->assertArrayHasKey('tax_credits', $result);
        $this->assertArrayHasKey('year', $result);

        $this->assertEquals(2025, $result['year']);
    }
}
