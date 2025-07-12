<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\ExpenseFilterRequest;
use App\Http\Requests\Expense\ExpenseRequest;
use App\Models\Expense;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private StatsService $statsService
    ) {}

    public function index(ExpenseFilterRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $query = Expense::forUser(auth()->id())
                ->dateRange($request->date_from, $request->date_to)
                ->category($request->category)
                ->isBusiness($request->is_business)
                ->recurring($request->recurring)
                ->amountRange($request->min, $request->max)
                ->vendor($request->vendor);

            $expenses = $query->orderBy($request->sort_by ?? 'date', $request->sort_direction ?? 'desc')
                ->paginate($request->per_page ?? 15);

            return $this->paginated($expenses, 'Expenses retrieved successfully', [
                'filters_applied' => array_filter($request->only([
                    'date_from', 'date_to', 'category', 'is_business',
                    'recurring', 'min', 'max', 'vendor'
                ]))
            ]);
        });
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $expense = Expense::create([
                ...$request->validated(),
                'user_id' => auth()->id(),
            ]);

            // Clear stats cache when new expense is created
            $this->statsService->clearStatsCache(auth()->id());

            return $this->created($expense, 'Expense created successfully');
        });
    }

    public function show(Expense $expense): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($expense) {
            // Ensure user owns this expense
            if ($expense->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to view this expense');
            }

            return $this->successWithData($expense, 'Expense retrieved successfully');
        });
    }

    public function update(ExpenseRequest $request, Expense $expense): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $expense) {
            // Ensure user owns this expense
            if ($expense->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to update this expense');
            }

            $expense->update($request->validated());

            // Clear stats cache when expense is updated
            $this->statsService->clearStatsCache(auth()->id());

            return $this->updated($expense->fresh(), 'Expense updated successfully');
        });
    }

    public function destroy(Expense $expense): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($expense) {
            // Ensure user owns this expense
            if ($expense->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to delete this expense');
            }

            $expense->delete();

            // Clear stats cache when expense is deleted
            $this->statsService->clearStatsCache(auth()->id());

            return $this->deleted('Expense deleted successfully');
        });
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'ids' => 'required|array|min:1|max:100',
                'ids.*' => 'integer|exists:expenses,id'
            ]);

            $deletedCount = Expense::whereIn('id', $request->ids)
                ->where('user_id', auth()->id())
                ->delete();

            // Clear stats cache when expenses are deleted
            $this->statsService->clearStatsCache(auth()->id());

            return $this->success(
                ['deleted_count' => $deletedCount],
                "Successfully deleted {$deletedCount} expense record(s)"
            );
        });
    }

    public function stats(ExpenseFilterRequest $request): JsonResponse
    {
        $filters = array_filter($request->only([
            'date_from', 'date_to', 'category', 'is_business', 'recurring'
        ]));

        $stats = $this->statsService->getExpenseStats(auth()->id(), $filters);

        return response()->json([
            'data' => $stats
        ]);
    }
}
