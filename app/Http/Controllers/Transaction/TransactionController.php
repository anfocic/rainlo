<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionFilterRequest;
use App\Http\Requests\Transaction\TransactionRequest;
use App\Models\Transaction;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected StatsService $statsService;

    public function __construct(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function index(TransactionFilterRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $query = Transaction::forUser(auth()->id())
                ->dateRange($request->date_from, $request->date_to)
                ->category($request->category)
                ->isBusiness($request->is_business)
                ->recurring($request->recurring)
                ->amountRange($request->min, $request->max);

            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->vendor) {
                $query->vendor($request->vendor);
            }

            if ($request->source) {
                $query->source($request->source);
            }

            $transactions = $query->orderBy($request->sort_by ?? 'date', $request->sort_direction ?? 'desc')
                ->paginate($request->per_page ?? 15);

            return $this->paginated($transactions, 'Transactions retrieved successfully', [
                'filters_applied' => array_filter($request->only([
                    'type', 'date_from', 'date_to', 'category', 'is_business',
                    'recurring', 'min', 'max', 'vendor', 'source'
                ]))
            ]);
        });
    }

    public function store(TransactionRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $transaction = Transaction::create([
                ...$request->validated(),
                'user_id' => auth()->id(),
            ]);

            // Clear stats cache when new transaction is created
            $this->statsService->clearStatsCache(auth()->id());

            return $this->created($transaction, ucfirst($transaction->type) . ' created successfully');
        });
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($transaction) {

            if ($transaction->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to view this transaction');
            }

            return $this->successWithData($transaction, 'Transaction retrieved successfully');
        });
    }

    public function update(TransactionRequest $request, Transaction $transaction): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $transaction) {

            if ($transaction->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to update this transaction');
            }

            $transaction->update($request->validated());

            $this->statsService->clearStatsCache(auth()->id());

            return $this->updated($transaction->fresh(), 'Transaction updated successfully');
        });
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($transaction) {

            if ($transaction->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to delete this transaction');
            }

            $transaction->delete();

            $this->statsService->clearStatsCache(auth()->id());

            return $this->deleted('Transaction deleted successfully');
        });
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->validate([
                'ids' => 'required|array|min:1|max:100',
                'ids.*' => 'integer|exists:transactions,id'
            ]);

            $deletedCount = Transaction::whereIn('id', $request->ids)
                ->where('user_id', auth()->id())
                ->delete();

            $this->statsService->clearStatsCache(auth()->id());

            return $this->success(
                ['deleted_count' => $deletedCount],
                "Successfully deleted {$deletedCount} transaction(s)"
            );
        });
    }

    public function stats(TransactionFilterRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $filters = array_filter($request->only([
                'type', 'date_from', 'date_to', 'category', 'is_business', 'recurring'
            ]));

            // Get cached stats from service
            $allStats = $this->statsService->getTransactionStats(auth()->id(), $filters);

            if ($request->type === 'income') {
                $stats = [
                    ...$allStats['income'],
                    'type' => 'income'
                ];
            } elseif ($request->type === 'expense') {
                $stats = [
                    ...$allStats['expense'],
                    'type' => 'expense'
                ];
            } else {
                $stats = $allStats;
            }

            return $this->successWithData($stats, 'Transaction statistics retrieved successfully', 200, [
                'filters_applied' => $filters,
                'cached' => true
            ]);
        });
    }
}
