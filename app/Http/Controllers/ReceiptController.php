<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    /**
     * Upload receipt for an expense
     */
    public function upload(Request $request, Expense $expense): JsonResponse
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        try {
            // Delete old receipt if exists
            if ($expense->receipt_url) {
                Storage::disk('public')->delete($expense->receipt_url);
            }

            // Store new receipt
            $path = $request->file('receipt')->store('receipts/' . auth()->id(), 'public');

            // Update expense with receipt URL
            $expense->update(['receipt_url' => $path]);

            return response()->json([
                'message' => 'Receipt uploaded successfully',
                'data' => [
                    'receipt_url' => $path,
                    'receipt_full_url' => Storage::disk('public')->url($path)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download receipt for an expense
     */
    public function download(Expense $expense): JsonResponse
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$expense->receipt_url) {
            return response()->json(['message' => 'No receipt found'], 404);
        }

        if (!Storage::disk('public')->exists($expense->receipt_url)) {
            return response()->json(['message' => 'Receipt file not found'], 404);
        }

        return response()->json([
            'data' => [
                'receipt_url' => $expense->receipt_url,
                'receipt_full_url' => Storage::disk('public')->url($expense->receipt_url),
                'download_url' => route('receipts.download-file', $expense)
            ]
        ]);
    }

    /**
     * Download receipt file directly
     */
    public function downloadFile(Expense $expense)
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$expense->receipt_url || !Storage::disk('public')->exists($expense->receipt_url)) {
            abort(404);
        }

        return Storage::disk('public')->download($expense->receipt_url);
    }

    /**
     * Delete receipt for an expense
     */
    public function delete(Expense $expense): JsonResponse
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$expense->receipt_url) {
            return response()->json(['message' => 'No receipt found'], 404);
        }

        try {
            // Delete file from storage
            Storage::disk('public')->delete($expense->receipt_url);

            // Remove receipt URL from expense
            $expense->update(['receipt_url' => null]);

            return response()->json([
                'message' => 'Receipt deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all receipts for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::forUser(auth()->id())
            ->whereNotNull('receipt_url')
            ->with(['user'])
            ->orderBy('date', 'desc')
            ->paginate($request->per_page ?? 15);

        $expenses->getCollection()->transform(function ($expense) {
            $expense->receipt_full_url = Storage::disk('public')->url($expense->receipt_url);
            return $expense;
        });

        return response()->json([
            'data' => $expenses->items(),
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
                'last_page' => $expenses->lastPage(),
            ]
        ]);
    }
}
