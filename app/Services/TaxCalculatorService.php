<?php

namespace App\Services;

use App\Domain\Tax\Contracts\TaxCalculatorInterface;
use App\Domain\Tax\TaxRates;

class TaxCalculatorService implements TaxCalculatorInterface
{

    public function calculateTax(float $annualIncome, string $maritalStatus = 'single', bool $hasChildren = false, ?float $spouseIncome = null): array
    {
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
            'effective_tax_rate' => $annualIncome > 0 ? round(($netTax / $annualIncome) * 100, 2) : 0,
            'marginal_tax_rate' => $this->calculateMarginalTaxRate($annualIncome, $maritalStatus, $spouseIncome),
        ];
    }

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

    private function calculateIncomeTax(float $income, string $maritalStatus, ?float $spouseIncome = null): float
    {
        $standardRateBand = $this->getStandardRateBand($maritalStatus, $spouseIncome);

        $standardRateAmount = min($income, $standardRateBand);
        $higherRateAmount = max(0, $income - $standardRateBand);

        return ($standardRateAmount * TaxRates::INCOME_TAX_RATES['standard_rate']) +
            ($higherRateAmount * TaxRates::INCOME_TAX_RATES['higher_rate']);
    }

    private function calculateUSC(float $income): float
    {
        $usc = 0;
        $previousLimit = 0;

        foreach (TaxRates::USC_BANDS as $band) {
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
                $bandStart = $previousLimit;
                $bandEnd = $bandLimit;

                if ($income > $bandStart) {
                    $taxableInBand = min($income, $bandEnd) - $bandStart;
                    $usc += $taxableInBand * $bandRate;
                }

                $previousLimit = $bandLimit;

                if ($income <= $bandLimit) {
                    break;
                }
            }
        }

        return $usc;
    }

    private function calculatePRSI(float $income): float
    {
        return $income * TaxRates::PRSI_RATE;
    }

    private function calculateTaxCredits(string $maritalStatus, bool $hasChildren): float
    {
        $credits = 0;

        if ($maritalStatus === 'married') {
            $credits += TaxRates::TAX_CREDITS['married_person'];
        } else {
            $credits += TaxRates::TAX_CREDITS['single_person'];

            if ($hasChildren) {
                $credits += TaxRates::TAX_CREDITS['single_parent_child_carer'];
            }
        }

        $credits += TaxRates::TAX_CREDITS['employee_paye'];

        return $credits;
    }

    private function calculateMarginalTaxRate(float $income, string $maritalStatus, ?float $spouseIncome = null): float
    {
        $standardRateBand = $this->getStandardRateBand($maritalStatus, $spouseIncome);

        $incomeTaxMarginalRate = $income >= $standardRateBand ?
            TaxRates::INCOME_TAX_RATES['higher_rate'] :
            TaxRates::INCOME_TAX_RATES['standard_rate'];

        $uscMarginalRate = 0;
        $previousLimit = 0;

        foreach (TaxRates::USC_BANDS as $band) {
            $bandLimit = $band['limit'];
            $bandRate = $band['rate'];

            if ($bandLimit === null) {
                if ($income > $previousLimit) {
                    $uscMarginalRate = $bandRate;
                }
                break;
            } else {
                if ($income > $previousLimit && $income <= $bandLimit) {
                    $uscMarginalRate = $bandRate;
                    break;
                }
                $previousLimit = $bandLimit;
            }
        }

        return round(($incomeTaxMarginalRate + $uscMarginalRate + TaxRates::PRSI_RATE) * 100, 2);
    }

    /**
     * RATE & BAND
     */

    private function getStandardRateBand(string $maritalStatus, ?float $spouseIncome = null): float
    {
        switch ($maritalStatus) {
            case 'single_parent':
                return TaxRates::INCOME_TAX_BANDS['single_parent'];

            case 'married':
                if ($spouseIncome === null || $spouseIncome <= 0) {
                    return TaxRates::INCOME_TAX_BANDS['married_one_income'];
                } else {
                    // Two incomes - can increase band by lower of €35,000 or spouse's income
                    $increase = min(TaxRates::INCOME_TAX_BANDS['married_two_incomes_max_increase'], $spouseIncome);
                    return TaxRates::INCOME_TAX_BANDS['married_two_incomes_base'] + $increase;
                }

            default:
                return TaxRates::INCOME_TAX_BANDS['single'];
        }
    }

    /**
     * SCENARIO
     */
    public function generateComparisonSummary(array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $incomes = $this->extractResults($results, 'annual_income');
        $netIncomes = $this->extractResults($results, 'net_income');
        $effectiveRates = $this->extractResults($results, 'effective_tax_rate');

        return [
            'highest_income' => [
                'scenario' => $this->createScenario($results, $incomes),
                'amount' => $this->getMax($incomes),
            ],
            'highest_net_income' => [
                'scenario' => $this->createScenario($results, $netIncomes),
                'amount' => $this->getMax($netIncomes),
            ],
            'lowest_effective_rate' => [
                'scenario' => $this->createScenario($results, $effectiveRates),
                'rate' => min($effectiveRates),
            ],
            'highest_effective_rate' => [
                'scenario' => $this->createScenario($results, $effectiveRates),
                'rate' => $this->getMax($effectiveRates),
            ],
        ];
    }

    private function extractResults(array $results, string $key): array
    {
        return array_column(array_column($results, 'calculation'), $key);
    }

    private function createScenario(array $results, $incomes)
    {
        return $results[array_search(max($incomes), $incomes)]['label'];
    }

    private function getMax(array $incomes)
    {
        return max($incomes);
    }
}
