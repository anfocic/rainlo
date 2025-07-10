<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\Income\IncomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\TaxReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Alternative public auth routes (for backward compatibility)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {

    // User info endpoint
    Route::get('/user', function (Request $request) {
        return response()->json([
            'data' => $request->user()
        ]);
    });

    // Auth routes that require authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', function (Request $request) {
            return response()->json(['data' => $request->user()]);
        });
    });

    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::delete('/', [ProfileController::class, 'destroy']);
    });

    // Income Management
    Route::prefix('incomes')->group(function () {
        Route::get('/', [IncomeController::class, 'index']);           // GET /api/incomes
        Route::post('/', [IncomeController::class, 'store']);          // POST /api/incomes
        Route::get('/stats', [IncomeController::class, 'stats']);      // GET /api/incomes/stats
        Route::post('/bulk-delete', [IncomeController::class, 'bulkDelete']); // POST /api/incomes/bulk-delete
        Route::get('/{income}', [IncomeController::class, 'show']);    // GET /api/incomes/{id}
        Route::put('/{income}', [IncomeController::class, 'update']);  // PUT /api/incomes/{id}
        Route::patch('/{income}', [IncomeController::class, 'update']); // PATCH /api/incomes/{id}
        Route::delete('/{income}', [IncomeController::class, 'destroy']); // DELETE /api/incomes/{id}
    });

    // Expense Management
    Route::prefix('expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index']);          // GET /api/expenses
        Route::post('/', [ExpenseController::class, 'store']);         // POST /api/expenses
        Route::get('/stats', [ExpenseController::class, 'stats']);     // GET /api/expenses/stats
        Route::post('/bulk-delete', [ExpenseController::class, 'bulkDelete']); // POST /api/expenses/bulk-delete
        Route::get('/{expense}', [ExpenseController::class, 'show']);  // GET /api/expenses/{id}
        Route::put('/{expense}', [ExpenseController::class, 'update']); // PUT /api/expenses/{id}
        Route::patch('/{expense}', [ExpenseController::class, 'update']); // PATCH /api/expenses/{id}
        Route::delete('/{expense}', [ExpenseController::class, 'destroy']); // DELETE /api/expenses/{id}
    });

    // Receipt Management
    Route::prefix('receipts')->group(function () {
        Route::get('/', [ReceiptController::class, 'index']);                    // GET /api/receipts
        Route::post('/{expense}/upload', [ReceiptController::class, 'upload']);  // POST /api/receipts/{expense}/upload
        Route::get('/{expense}/download', [ReceiptController::class, 'download']); // GET /api/receipts/{expense}/download
        Route::get('/{expense}/file', [ReceiptController::class, 'downloadFile'])->name('receipts.download-file'); // Direct file download
        Route::delete('/{expense}', [ReceiptController::class, 'delete']);       // DELETE /api/receipts/{expense}
    });

    // Tax Reports
    Route::prefix('tax-reports')->group(function () {
        Route::get('/categories', [TaxReportController::class, 'categories']);           // GET /api/tax-reports/categories
        Route::get('/{year}', [TaxReportController::class, 'annual']);                  // GET /api/tax-reports/2024
        Route::get('/{year}/{quarter}', [TaxReportController::class, 'quarterly']);     // GET /api/tax-reports/2024/1
        Route::get('/{year}/deductions', [TaxReportController::class, 'deductions']);   // GET /api/tax-reports/2024/deductions
    });

    // Alternative resource routes (for Laravel conventions)
    Route::apiResource('incomes', IncomeController::class);
    Route::apiResource('expenses', ExpenseController::class);

    // Dashboard/Analytics endpoints
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', function (Request $request) {
            $userId = auth()->id();

            return response()->json([
                'data' => [
                    'total_income' => \App\Models\Income::forUser($userId)->sum('amount'),
                    'total_expenses' => \App\Models\Expense::forUser($userId)->sum('amount'),
                    'net_income' => \App\Models\Income::forUser($userId)->sum('amount') -
                                   \App\Models\Expense::forUser($userId)->sum('amount'),
                    'income_count' => \App\Models\Income::forUser($userId)->count(),
                    'expense_count' => \App\Models\Expense::forUser($userId)->count(),
                    'business_income' => \App\Models\Income::forUser($userId)->where('is_business', true)->sum('amount'),
                    'business_expenses' => \App\Models\Expense::forUser($userId)->where('is_business', true)->sum('amount'),
                ]
            ]);
        });

        Route::get('/recent-transactions', function (Request $request) {
            $userId = auth()->id();
            $limit = $request->get('limit', 10);

            $recentIncomes = \App\Models\Income::forUser($userId)
                ->latest('date')
                ->limit($limit)
                ->get()
                ->map(function ($income) {
                    return array_merge($income->toArray(), ['type' => 'income']);
                });

            $recentExpenses = \App\Models\Expense::forUser($userId)
                ->latest('date')
                ->limit($limit)
                ->get()
                ->map(function ($expense) {
                    return array_merge($expense->toArray(), ['type' => 'expense']);
                });

            $transactions = $recentIncomes->concat($recentExpenses)
                ->sortByDesc('date')
                ->take($limit)
                ->values();

            return response()->json([
                'data' => $transactions
            ]);
        });
    });
});
