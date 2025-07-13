<?php

namespace App\Domain\Tax\Contracts;

interface TaxCalculatorInterface
{
    /**
     * Calculate tax for given income and circumstances
     *
     * @param float $annualIncome
     * @param string $maritalStatus
     * @param bool $hasChildren
     * @param float|null $spouseIncome
     * @return array
     */
    public function calculateTax(
        float $annualIncome,
        string $maritalStatus = 'single',
        bool $hasChildren = false,
        ?float $spouseIncome = null
    ): array;

    /**
     * Calculate monthly breakdown from annual calculation
     *
     * @param array $annualCalculation
     * @return array
     */
    public function calculateMonthlyBreakdown(array $annualCalculation): array;
}
