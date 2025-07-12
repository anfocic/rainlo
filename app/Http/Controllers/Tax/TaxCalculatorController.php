<?php

namespace App\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\TaxCalculationRequest;
use App\Services\TaxCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxCalculatorController extends Controller
{
    private TaxCalculatorService $taxCalculatorService;

    public function __construct(TaxCalculatorService $taxCalculatorService)
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
            // Cache tax rates for 24 hours since they don't change frequently
            $rates = cache()->remember('tax_rates_2025', 86400, function () {
                return $this->taxCalculatorService->getTaxRatesAndBands();
            });

            return $this->successWithData($rates, 'Tax rates and bands retrieved successfully', 200, [
                'last_updated' => '2025-01-01',
                'source' => 'Revenue.ie - Irish Tax and Customs',
                'cached' => true,
            ]);
        });
    }

    public function compareScenarios(Request $request): JsonResponse
    {
        $request->validate([
            'scenarios' => 'required|array|min:1|max:5',
            'scenarios.*.annual_income' => 'required|numeric|min:0|max:10000000',
            'scenarios.*.marital_status' => 'required|string|in:single,married,single_parent',
            'scenarios.*.has_children' => 'sometimes|boolean',
            'scenarios.*.spouse_income' => 'sometimes|nullable|numeric|min:0|max:10000000',
            'scenarios.*.label' => 'sometimes|string|max:100',
        ]);

        try {
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

            return response()->json([
                'message' => 'Tax comparison completed successfully',
                'data' => [
                    'scenarios' => $results,
                    'comparison_summary' => $this->generateComparisonSummary($results),
                ],
                'calculation_date' => now()->toISOString(),
                'tax_year' => 2025,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Tax comparison failed',
                'error' => 'An error occurred while comparing tax scenarios',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function marginalRate(Request $request): JsonResponse
    {
        $request->validate([
            'annual_income' => 'required|numeric|min:0|max:10000000',
            'marital_status' => 'required|string|in:single,married,single_parent',
            'spouse_income' => 'sometimes|nullable|numeric|min:0|max:10000000',
        ]);

        try {
            // Calculate tax at current income
            $currentCalculation = $this->taxCalculatorService->calculateTax(
                annualIncome: $request->annual_income,
                maritalStatus: $request->marital_status,
                hasChildren: false,
                spouseIncome: $request->spouse_income
            );

            // Calculate tax at income + €1000 to show marginal effect
            $higherCalculation = $this->taxCalculatorService->calculateTax(
                annualIncome: $request->annual_income + 1000,
                maritalStatus: $request->marital_status,
                hasChildren: false,
                spouseIncome: $request->spouse_income
            );

            $marginalTaxOnExtra1000 = $higherCalculation['breakdown']['net_tax'] - $currentCalculation['breakdown']['net_tax'];
            $effectiveMarginalRate = ($marginalTaxOnExtra1000 / 1000) * 100;

            return response()->json([
                'message' => 'Marginal tax rate calculated successfully',
                'data' => [
                    'annual_income' => $request->annual_income,
                    'marginal_tax_rate' => $currentCalculation['marginal_tax_rate'],
                    'effective_marginal_rate' => round($effectiveMarginalRate, 2),
                    'tax_on_next_1000' => round($marginalTaxOnExtra1000, 2),
                    'net_from_next_1000' => round(1000 - $marginalTaxOnExtra1000, 2),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Marginal rate calculation failed',
                'error' => 'An error occurred while calculating marginal tax rate',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function generateComparisonSummary(array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $incomes = array_column(array_column($results, 'calculation'), 'annual_income');
        $netIncomes = array_column(array_column($results, 'calculation'), 'net_income');
        $effectiveRates = array_column(array_column($results, 'calculation'), 'effective_tax_rate');

        return [
            'highest_income' => [
                'scenario' => $results[array_search(max($incomes), $incomes)]['label'],
                'amount' => max($incomes),
            ],
            'highest_net_income' => [
                'scenario' => $results[array_search(max($netIncomes), $netIncomes)]['label'],
                'amount' => max($netIncomes),
            ],
            'lowest_effective_rate' => [
                'scenario' => $results[array_search(min($effectiveRates), $effectiveRates)]['label'],
                'rate' => min($effectiveRates),
            ],
            'highest_effective_rate' => [
                'scenario' => $results[array_search(max($effectiveRates), $effectiveRates)]['label'],
                'rate' => max($effectiveRates),
            ],
        ];
    }
}
