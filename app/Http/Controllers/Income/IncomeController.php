<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Http\Requests\Income\IncomeFilterRequest;
use App\Http\Requests\Income\IncomeRequest;
use App\Models\Income;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
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

        return response()->json([
            'data' => $incomes->items(),
            'pagination' => [
                'current_page' => $incomes->currentPage(),
                'per_page' => $incomes->perPage(),
                'total' => $incomes->total(),
                'last_page' => $incomes->lastPage(),
            ],
            'filters_applied' => array_filter($request->only([
                'date_from', 'date_to', 'category', 'is_business',
                'recurring', 'min', 'max'
            ]))
        ]);
    }

    public function store(IncomeRequest $request): JsonResponse
    {
        $income = Income::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Income created successfully',
            'data' => $income
        ], 201);
    }

    public function show(Income $income): JsonResponse
    {
        // Ensure user owns this income
        if ($income->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $income
        ]);
    }

    public function update(IncomeRequest $request, Income $income): JsonResponse
    {
        // Ensure user owns this income
        if ($income->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $income->update($request->validated());

        return response()->json([
            'message' => 'Income updated successfully',
            'data' => $income
        ]);
    }

    public function destroy(Income $income): JsonResponse
    {
        // Ensure user owns this income
        if ($income->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $income->delete();

        return response()->json([
            'message' => 'Income deleted successfully'
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:incomes,id'
        ]);

        $deletedCount = Income::whereIn('id', $request->ids)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'message' => "Successfully deleted {$deletedCount} income records"
        ]);
    }

    public function stats(IncomeFilterRequest $request): JsonResponse
    {
        $query = Income::forUser(auth()->id())
            ->dateRange($request->date_from, $request->date_to)
            ->category($request->category)
            ->isBusiness($request->is_business)
            ->recurring($request->recurring);

        $stats = [
            'total_amount' => $query->sum('amount'),
            'count' => $query->count(),
            'average' => $query->avg('amount'),
            'business_income' => Income::forUser(auth()->id())->where('is_business', true)->sum('amount'),
            'personal_income' => Income::forUser(auth()->id())->where('is_business', false)->sum('amount'),
            'recurring_income' => Income::forUser(auth()->id())->where('recurring', true)->sum('amount'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}
