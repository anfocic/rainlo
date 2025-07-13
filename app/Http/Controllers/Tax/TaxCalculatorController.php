<?php

namespace App\Http\Controllers\Tax;

use App\Domain\Tax\Contracts\TaxCalculatorInterface;
use App\Domain\Tax\TaxRates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\MarginalRateRequest;
use App\Http\Requests\Tax\ScenarioRequest;
use App\Http\Requests\Tax\TaxCalculationRequest;
use Illuminate\Http\JsonResponse;

class TaxCalculatorController extends Controller
{
    private TaxCalculatorInterface $taxCalculatorService;

    public function __construct(TaxCalculatorInterface $taxCalculatorService)
    {
        $this->taxCalculatorService = $taxCalculatorService;
    }

    public function calculate(TaxCalculationRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $calculation = $this->taxCalculatorService->calculateTax(
                annualIncome: $request->annual_income,
                maritalStatus: $request->marital_status,
                hasChildren: $request->has_children ?? false,
                spouseIncome: $request->spouse_income
            );

            $monthlyBreakdown = $this->taxCalculatorService->calculateMonthlyBreakdown($calculation);

            return $this->successWithData([
                'annual' => $calculation,
                'monthly' => $monthlyBreakdown,
            ], 'Tax calculation completed successfully', 200, [
                'calculation_date' => now()->toISOString(),
                'tax_year' => 2025,
            ]);
        });
    }

    public function getRates(): JsonResponse
    {
        return $this->executeWithErrorHandling(function () {

            $rates = cache()->remember('tax_rates_2025', 86400, function () {
                return TaxRates::asArray();
            });

            return $this->successWithData($rates, 'Tax rates and bands retrieved successfully', 200, [
                'last_updated' => '2025-01-01',
                'source' => 'Revenue.ie - Irish Tax and Customs',
                'cached' => true,
            ]);
        });
    }

    public function marginalRate(MarginalRateRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $currentCalculation = $this->taxCalculatorService->calculateTax(
                annualIncome: $request->annual_income,
                maritalStatus: $request->marital_status,
                spouseIncome: $request->spouse_income
            );

            // Calculate tax at income + €1000 to show marginal effect
            $higherCalculation = $this->taxCalculatorService->calculateTax(
                annualIncome: $request->annual_income + 1000,
                maritalStatus: $request->marital_status,
                spouseIncome: $request->spouse_income
            );

            $marginalTaxOnExtra1000 = $higherCalculation['breakdown']['net_tax'] - $currentCalculation['breakdown']['net_tax'];
            $effectiveMarginalRate = ($marginalTaxOnExtra1000 / 1000) * 100;

            return $this->successWithData([
                'annual_income' => $request->annual_income,
                'marginal_tax_rate' => $currentCalculation['marginal_tax_rate'],
                'effective_marginal_rate' => round($effectiveMarginalRate, 2),
                'tax_on_next_1000' => round($marginalTaxOnExtra1000, 2),
                'net_from_next_1000' => round(1000 - $marginalTaxOnExtra1000, 2),
            ], 'Marginal tax rate calculated successfully', 200, [
                'calculation_date' => now()->toISOString(),
                'tax_year' => 2025,
            ]);
        });


    }

    public function compareScenarios(ScenarioRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $results = [];

            foreach ($request->scenarios as $index => $scenario) {
                $calculation = $this->taxCalculatorService->calculateTax(
                    annualIncome: $scenario['annual_income'],
                    maritalStatus: $scenario['marital_status'],
                    hasChildren: $scenario['has_children'] ?? false,
                    spouseIncome: $scenario['spouse_income'] ?? null
                );

                $results[] = [
                    'scenario_id' => $index + 1,
                    'label' => $scenario['label'] ?? "Scenario " . ($index + 1),
                    'calculation' => $calculation,
                ];
            }

            return $this->successWithData([
                'scenarios' => $results,
                'comparison_summary' => $this->taxCalculatorService->generateComparisonSummary($results),
            ], 'Tax comparison completed successfully', 200, [
                'calculation_date' => now()->toISOString(),
                'tax_year' => 2025,
            ]);
        });
    }
}
