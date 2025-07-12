<?php

namespace App\Services;

class TaxCalculatorService
{
    // 2025 Tax Rates and Bands for Ireland
    private const INCOME_TAX_RATES = [
        'standard_rate' => 0.20,
        'higher_rate' => 0.40,
    ];

    private const INCOME_TAX_BANDS = [
        'single' => 44000,
        'married_one_income' => 53000,
        'married_two_incomes_base' => 53000,
        'married_two_incomes_max_increase' => 35000,
        'single_parent' => 48000,
    ];

    private const USC_BANDS = [
        ['limit' => 12012, 'rate' => 0.005],
        ['limit' => 27382, 'rate' => 0.02],   // 12012 + 15370
        ['limit' => 70044, 'rate' => 0.03],   // 27382 + 42662
        ['limit' => null, 'rate' => 0.08],    // Balance
    ];

    private const PRSI_RATE = 0.042; // 4.2% for Class A1 employees

    private const TAX_CREDITS = [
        'single_person' => 2000,
        'married_person' => 4000,
        'employee_paye' => 2000,
        'single_parent_child_carer' => 1900,
    ];

    /**
     * Calculate total tax liability for given income and circumstances
     */
    public function calculateTax(
        float $annualIncome,
        string $maritalStatus = 'single',
        bool $hasChildren = false,
        ?float $spouseIncome = null
    ): array {
        $incomeTax = $this->calculateIncomeTax($annualIncome, $maritalStatus, $spouseIncome);
        $usc = $this->calculateUSC($annualIncome);
        $prsi = $this->calculatePRSI($annualIncome);
        $taxCredits = $this->calculateTaxCredits($maritalStatus, $hasChildren);

        $grossTax = $incomeTax + $usc + $prsi;
        $netTax = max(0, $grossTax - $taxCredits);
        $netIncome = $annualIncome - $netTax;

        return [
            'annual_income' => $annualIncome,
            'marital_status' => $maritalStatus,
            'has_children' => $hasChildren,
            'spouse_income' => $spouseIncome,
            'breakdown' => [
                'income_tax' => round($incomeTax, 2),
                'usc' => round($usc, 2),
                'prsi' => round($prsi, 2),
                'gross_tax' => round($grossTax, 2),
                'tax_credits' => round($taxCredits, 2),
                'net_tax' => round($netTax, 2),
            ],
            'net_income' => round($netIncome, 2),
            'effective_tax_rate' => round(($netTax / $annualIncome) * 100, 2),
            'marginal_tax_rate' => $this->calculateMarginalTaxRate($annualIncome, $maritalStatus, $spouseIncome),
        ];
    }

    /**
     * Calculate Income Tax (PAYE)
     */
    private function calculateIncomeTax(float $income, string $maritalStatus, ?float $spouseIncome = null): float
    {
        $standardRateBand = $this->getStandardRateBand($maritalStatus, $spouseIncome);

        $standardRateAmount = min($income, $standardRateBand);
        $higherRateAmount = max(0, $income - $standardRateBand);

        return ($standardRateAmount * self::INCOME_TAX_RATES['standard_rate']) +
               ($higherRateAmount * self::INCOME_TAX_RATES['higher_rate']);
    }

    /**
     * Calculate Universal Social Charge (USC)
     */
    private function calculateUSC(float $income): float
    {
        $usc = 0;
        $previousLimit = 0;

        foreach (self::USC_BANDS as $band) {
            $bandLimit = $band['limit'];
            $bandRate = $band['rate'];

            if ($bandLimit === null) {
                // Final band - all remaining income above previous limit
                if ($income > $previousLimit) {
                    $taxableInBand = $income - $previousLimit;
                    $usc += $taxableInBand * $bandRate;
                }
                break;
            } else {
                // Calculate taxable amount in this band
                $bandStart = $previousLimit;
                $bandEnd = $bandLimit;

                if ($income > $bandStart) {
                    $taxableInBand = min($income, $bandEnd) - $bandStart;
                    $usc += $taxableInBand * $bandRate;
                }

                $previousLimit = $bandLimit;

                // If income doesn't reach this band limit, we're done
                if ($income <= $bandLimit) {
                    break;
                }
            }
        }

        return $usc;
    }

    /**
     * Calculate PRSI (Pay Related Social Insurance)
     */
    private function calculatePRSI(float $income): float
    {
        // PRSI is calculated on all income with no upper limit for Class A1
        return $income * self::PRSI_RATE;
    }

    /**
     * Calculate applicable tax credits
     */
    private function calculateTaxCredits(string $maritalStatus, bool $hasChildren): float
    {
        $credits = 0;

        // Personal tax credit
        if ($maritalStatus === 'married') {
            $credits += self::TAX_CREDITS['married_person'];
        } else {
            $credits += self::TAX_CREDITS['single_person'];

            // Single parent child carer credit
            if ($hasChildren) {
                $credits += self::TAX_CREDITS['single_parent_child_carer'];
            }
        }

        // Employee PAYE tax credit
        $credits += self::TAX_CREDITS['employee_paye'];

        return $credits;
    }

    /**
     * Get standard rate band based on marital status and spouse income
     */
    private function getStandardRateBand(string $maritalStatus, ?float $spouseIncome = null): float
    {
        switch ($maritalStatus) {
            case 'single':
                return self::INCOME_TAX_BANDS['single'];

            case 'single_parent':
                return self::INCOME_TAX_BANDS['single_parent'];

            case 'married':
                if ($spouseIncome === null || $spouseIncome <= 0) {
                    return self::INCOME_TAX_BANDS['married_one_income'];
                } else {
                    // Two incomes - can increase band by lower of €35,000 or spouse's income
                    $increase = min(self::INCOME_TAX_BANDS['married_two_incomes_max_increase'], $spouseIncome);
                    return self::INCOME_TAX_BANDS['married_two_incomes_base'] + $increase;
                }

            default:
                return self::INCOME_TAX_BANDS['single'];
        }
    }

    /**
     * Calculate marginal tax rate (rate on next euro earned)
     */
    private function calculateMarginalTaxRate(float $income, string $maritalStatus, ?float $spouseIncome = null): float
    {
        $standardRateBand = $this->getStandardRateBand($maritalStatus, $spouseIncome);

        // Determine income tax marginal rate
        $incomeTaxMarginalRate = $income >= $standardRateBand ?
            self::INCOME_TAX_RATES['higher_rate'] :
            self::INCOME_TAX_RATES['standard_rate'];

        // Determine USC marginal rate based on which band the income falls into
        $uscMarginalRate = 0;
        $previousLimit = 0;

        foreach (self::USC_BANDS as $band) {
            $bandLimit = $band['limit'];
            $bandRate = $band['rate'];

            if ($bandLimit === null) {
                // Final band - if income is above previous limit, use this rate
                if ($income > $previousLimit) {
                    $uscMarginalRate = $bandRate;
                }
                break;
            } else {
                // If income falls within this band, use this rate
                if ($income > $previousLimit && $income <= $bandLimit) {
                    $uscMarginalRate = $bandRate;
                    break;
                }
                $previousLimit = $bandLimit;
            }
        }

        // PRSI is always the same rate
        $prsiMarginalRate = self::PRSI_RATE;

        return round(($incomeTaxMarginalRate + $uscMarginalRate + $prsiMarginalRate) * 100, 2);
    }

    /**
     * Get current tax rates and bands for reference
     */
    public function getTaxRatesAndBands(): array
    {
        return [
            'income_tax' => [
                'rates' => self::INCOME_TAX_RATES,
                'bands' => self::INCOME_TAX_BANDS,
            ],
            'usc' => [
                'bands' => self::USC_BANDS,
            ],
            'prsi' => [
                'rate' => self::PRSI_RATE,
            ],
            'tax_credits' => self::TAX_CREDITS,
            'year' => 2025,
        ];
    }

    /**
     * Calculate monthly breakdown
     */
    public function calculateMonthlyBreakdown(array $annualCalculation): array
    {
        $monthly = [];
        foreach ($annualCalculation['breakdown'] as $key => $value) {
            $monthly[$key] = round($value / 12, 2);
        }

        return [
            'monthly_gross_income' => round($annualCalculation['annual_income'] / 12, 2),
            'monthly_breakdown' => $monthly,
            'monthly_net_income' => round($annualCalculation['net_income'] / 12, 2),
        ];
    }
}
