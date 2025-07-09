<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\ExpenseFilterRequest;
use App\Http\Requests\Expense\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(ExpenseFilterRequest $request): JsonResponse
    {
        $query = Expense::forUser(auth()->id())
            ->dateRange($request->date_from, $request->date_to)
            ->category($request->category)
            ->isBusiness($request->is_business)
            ->recurring($request->recurring)
            ->amountRange($request->min, $request->max)
            ->vendor($request->vendor);

        $expenses = $query->orderBy($request->sort_by ?? 'date', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $expenses->items(),
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
                'last_page' => $expenses->lastPage(),
            ],
            'filters_applied' => array_filter($request->only([
                'date_from', 'date_to', 'category', 'is_business',
                'recurring', 'min', 'max', 'vendor'
            ]))
        ]);
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        $expense = Expense::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Expense created successfully',
            'data' => $expense
        ], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $expense
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense): JsonResponse
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $expense->update($request->validated());

        return response()->json([
            'message' => 'Expense updated successfully',
            'data' => $expense
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully'
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:expenses,id'
        ]);

        $deletedCount = Expense::whereIn('id', $request->ids)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'message' => "Successfully deleted {$deletedCount} expense records"
        ]);
    }

    public function stats(ExpenseFilterRequest $request): JsonResponse
    {
        $query = Expense::forUser(auth()->id())
            ->dateRange($request->date_from, $request->date_to)
            ->category($request->category)
            ->isBusiness($request->is_business)
            ->recurring($request->recurring);

        $stats = [
            'total_amount' => $query->sum('amount'),
            'count' => $query->count(),
            'average' => $query->avg('amount'),
            'business_expenses' => Expense::forUser(auth()->id())->where('is_business', true)->sum('amount'),
            'personal_expenses' => Expense::forUser(auth()->id())->where('is_business', false)->sum('amount'),
            'recurring_expenses' => Expense::forUser(auth()->id())->where('recurring', true)->sum('amount'),
            'top_categories' => Expense::forUser(auth()->id())
                ->selectRaw('category, SUM(amount) as total')
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'top_vendors' => Expense::forUser(auth()->id())
                ->selectRaw('vendor, SUM(amount) as total')
                ->whereNotNull('vendor')
                ->groupBy('vendor')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}
