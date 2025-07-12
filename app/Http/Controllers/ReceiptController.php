<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    /**
     * Upload receipt for an expense
     */
    public function upload(Request $request, Expense $expense): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $expense) {
            // Ensure user owns this expense
            if ($expense->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to upload receipts for this expense');
            }

            $request->validate([
                'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // 10MB max
            ]);

            // Delete old receipt if exists
            if ($expense->receipt_url) {
                Storage::disk('public')->delete($expense->receipt_url);
            }

            // Store new receipt with organized folder structure
            $path = $request->file('receipt')->store(
                'receipts/' . auth()->id() . '/' . date('Y/m'),
                'public'
            );

            // Update expense with receipt URL
            $expense->update(['receipt_url' => $path]);

            return $this->successWithData([
                'receipt_url' => $path,
                'receipt_full_url' => Storage::disk('public')->url($path),
                'expense' => $expense->fresh()
            ], 'Receipt uploaded successfully');
        });
    }

    /**
     * Get receipt information for an expense
     */
    public function show(Expense $expense): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($expense) {
            // Ensure user owns this expense
            if ($expense->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to view this receipt');
            }

            if (!$expense->receipt_url) {
                return $this->notFound('No receipt found for this expense');
            }

            if (!Storage::disk('public')->exists($expense->receipt_url)) {
                return $this->notFound('Receipt file not found on storage');
            }

            $fileInfo = [
                'receipt_url' => $expense->receipt_url,
                'receipt_full_url' => Storage::disk('public')->url($expense->receipt_url),
                'file_size' => Storage::disk('public')->size($expense->receipt_url),
                'file_type' => Storage::disk('public')->mimeType($expense->receipt_url),
                'uploaded_at' => $expense->updated_at,
                'expense' => $expense
            ];

            return $this->successWithData($fileInfo, 'Receipt information retrieved successfully');
        });
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
     * Download receipt file for an expense
     */
    public function downloadFile(Expense $expense): Response
    {
        // Ensure user owns this expense
        if ($expense->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (!$expense->receipt_url) {
            abort(404, 'No receipt found');
        }

        if (!Storage::disk('public')->exists($expense->receipt_url)) {
            abort(404, 'Receipt file not found');
        }

        $filePath = Storage::disk('public')->path($expense->receipt_url);
        $fileName = 'receipt_' . $expense->id . '_' . basename($expense->receipt_url);

        return response()->download($filePath, $fileName);
    }

    /**
     * Delete receipt for an expense
     */
    public function destroy(Expense $expense): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($expense) {
            // Ensure user owns this expense
            if ($expense->user_id !== auth()->id()) {
                return $this->forbidden('You do not have permission to delete this receipt');
            }

            if (!$expense->receipt_url) {
                return $this->notFound('No receipt found for this expense');
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($expense->receipt_url)) {
                Storage::disk('public')->delete($expense->receipt_url);
            }

            // Remove receipt URL from expense
            $expense->update(['receipt_url' => null]);

            return $this->deleted('Receipt deleted successfully');
        });
    }

    /**
     * List all receipts for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $query = Expense::forUser(auth()->id())
                ->whereNotNull('receipt_url')
                ->orderBy('date', 'desc');

            // Add filtering options
            if ($request->date_from) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->where('date', '<=', $request->date_to);
            }
            if ($request->category) {
                $query->where('category', $request->category);
            }

            $expenses = $query->paginate($request->per_page ?? 15);

            $expenses->getCollection()->transform(function ($expense) {
                $expense->receipt_full_url = Storage::disk('public')->url($expense->receipt_url);
                $expense->file_size = Storage::disk('public')->size($expense->receipt_url);
                $expense->file_type = Storage::disk('public')->mimeType($expense->receipt_url);
                return $expense;
            });

            return $this->paginated($expenses, 'Receipts retrieved successfully', [
                'filters_applied' => array_filter($request->only(['date_from', 'date_to', 'category']))
            ]);
        });
    }
}
