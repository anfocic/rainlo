<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PdfExportService;
use App\Services\TaxCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PdfExportController extends Controller
{
    private PdfExportService $pdfExportService;
    private TaxCalculatorService $taxCalculatorService;

    public function __construct(
        PdfExportService $pdfExportService,
        TaxCalculatorService $taxCalculatorService
    ) {
        $this->pdfExportService = $pdfExportService;
        $this->taxCalculatorService = $taxCalculatorService;
    }

    /**
     * Export expenses to PDF
     */
    public function exportExpenses(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'category' => 'nullable|string|max:100',
                'is_business' => 'nullable|boolean',
            ]);

            /** @var User $user */
            $user = auth()->user();

            $pdfContent = $this->pdfExportService->exportExpenses(
                user: $user,
                dateFrom: $request->date_from,
                dateTo: $request->date_to,
                category: $request->category,
                isBusiness: $request->is_business
            );

            $filename = 'expenses_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
            ]);
        });
    }

    /**
     * Export incomes to PDF
     */
    public function exportIncomes(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'category' => 'nullable|string|max:100',
                'is_business' => 'nullable|boolean',
            ]);

            /** @var User $user */
            $user = auth()->user();

            $pdfContent = $this->pdfExportService->exportIncomes(
                user: $user,
                dateFrom: $request->date_from,
                dateTo: $request->date_to,
                category: $request->category,
                isBusiness: $request->is_business
            );

            $filename = 'incomes_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
            ]);
        });
    }

    /**
     * Export financial summary to PDF
     */
    public function exportFinancialSummary(Request $request): Response
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            /** @var User $user */
            $user = auth()->user();

            $pdfContent = $this->pdfExportService->exportFinancialSummary(
                user: $user,
                dateFrom: $request->date_from,
                dateTo: $request->date_to
            );

            $filename = 'financial_summary_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
            ]);
        });
    }

    /**
     * Export tax calculation to PDF
     */
    public function exportTaxCalculation(Request $request): Response
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'annual_income' => 'required|numeric|min:0|max:10000000',
                'marital_status' => 'required|string|in:single,married,single_parent',
                'has_children' => 'sometimes|boolean',
                'spouse_income' => 'sometimes|nullable|numeric|min:0|max:10000000',
            ]);

            // Calculate tax using the service
            $calculation = $this->taxCalculatorService->calculateTax(
                annualIncome: $request->annual_income,
                maritalStatus: $request->marital_status,
                hasChildren: $request->has_children ?? false,
                spouseIncome: $request->spouse_income
            );

            $monthlyBreakdown = $this->taxCalculatorService->calculateMonthlyBreakdown($calculation);

            $taxData = [
                'annual' => $calculation,
                'monthly' => $monthlyBreakdown,
            ];

            /** @var User $user */
            $user = auth()->user();

            $metadata = [
                'user' => $user,
                'generated_at' => now(),
                'tax_year' => 2025,
                'calculation_date' => now(),
            ];

            $pdfContent = $this->pdfExportService->exportTaxCalculation($taxData, $metadata);

            $filename = 'tax_calculation_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
            ]);
        });
    }

    /**
     * Preview expenses PDF in browser
     */
    public function previewExpenses(Request $request): Response
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'category' => 'nullable|string|max:100',
                'is_business' => 'nullable|boolean',
            ]);

            /** @var User $user */
            $user = auth()->user();

            $pdfContent = $this->pdfExportService->exportExpenses(
                user: $user,
                dateFrom: $request->date_from,
                dateTo: $request->date_to,
                category: $request->category,
                isBusiness: $request->is_business
            );

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]);
        });
    }

    /**
     * Preview incomes PDF in browser
     */
    public function previewIncomes(Request $request): Response
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'category' => 'nullable|string|max:100',
                'is_business' => 'nullable|boolean',
            ]);

            /** @var User $user */
            $user = auth()->user();

            $pdfContent = $this->pdfExportService->exportIncomes(
                user: $user,
                dateFrom: $request->date_from,
                dateTo: $request->date_to,
                category: $request->category,
                isBusiness: $request->is_business
            );

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]);
        });
    }

    /**
     * Preview financial summary PDF in browser
     */
    public function previewFinancialSummary(Request $request): Response
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            /** @var User $user */
            $user = auth()->user();

            $pdfContent = $this->pdfExportService->exportFinancialSummary(
                user: $user,
                dateFrom: $request->date_from,
                dateTo: $request->date_to
            );

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]);
        });
    }
}
