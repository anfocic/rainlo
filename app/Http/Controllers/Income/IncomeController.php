<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Http\Requests\Income\IncomeFilterRequest;
use App\Http\Requests\Income\IncomeRequest;
use App\Models\Income;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function __construct(
        private StatsService $statsService
    ) {}
    public function index(IncomeFilterRequest $request): JsonResponse
    {
        $query = Income::forUser(auth()->id())
            ->dateRange($request->date_from, $request->date_to)
            ->category($request->category)
            ->isBusiness($request->is_business)
            ->recurring($request->recurring)
            ->amountRange($request->min, $request->max);

        $incomes = $query->orderBy($request->sort_by ?? 'date', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->paginated($incomes, 'Incomes retrieved successfully', [
            'filters_applied' => array_filter($request->only([
                'date_from', 'date_to', 'category', 'is_business',
                'recurring', 'min', 'max'
            ]))
        ]);
    }

    public function store(IncomeRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $income = Income::create([
                ...$request->validated(),
                'user_id' => auth()->id(),
            ]);

            // Clear stats cache when new income is created
            $this->statsService->clearStatsCache(auth()->id());

            return $this->created($income, 'Income created successfully');
        });
    }

    public function show(Income $income): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($income) {
            // Ensure user owns this income
            if ($income->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to view this income');
            }

            return $this->successWithData($income, 'Income retrieved successfully');
        });
    }

    public function update(IncomeRequest $request, Income $income): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $income) {
            // Ensure user owns this income
            if ($income->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to update this income');
            }

            $income->update($request->validated());

            // Clear stats cache when income is updated
            $this->statsService->clearStatsCache(auth()->id());

            return $this->updated($income->fresh(), 'Income updated successfully');
        });
    }

    public function destroy(Income $income): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($income) {
            // Ensure user owns this income
            if ($income->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to delete this income');
            }

            $income->delete();

            // Clear stats cache when income is deleted
            $this->statsService->clearStatsCache(auth()->id());

            return $this->deleted('Income deleted successfully');
        });
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'ids' => 'required|array|min:1|max:100',
                'ids.*' => 'integer|exists:incomes,id'
            ]);

            $deletedCount = Income::whereIn('id', $request->ids)
                ->where('user_id', auth()->id())
                ->delete();

            // Clear stats cache when incomes are deleted
            $this->statsService->clearStatsCache(auth()->id());

            return $this->success(
                ['deleted_count' => $deletedCount],
                "Successfully deleted {$deletedCount} income record(s)"
            );
        });
    }

    public function stats(IncomeFilterRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $filters = array_filter($request->only([
                'date_from', 'date_to', 'category', 'is_business', 'recurring'
            ]));

            $stats = $this->statsService->getIncomeStats(auth()->id(), $filters);

            return $this->successWithData($stats, 'Income statistics retrieved successfully', 200, [
                'filters_applied' => $filters
            ]);
        });
    }
}
