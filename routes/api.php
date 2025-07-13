<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Tax\TaxCalculatorController;
use App\Http\Controllers\Transaction\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/health', [HealthController::class, 'check']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [DashboardController::class, 'user']);

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', function (Request $request) {
            return response()->json(['data' => $request->user()]);
        });
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('/stats', [TransactionController::class, 'stats']);
        Route::post('/bulk-delete', [TransactionController::class, 'bulkDelete']);
        Route::get('/{transaction}', [TransactionController::class, 'show']);
        Route::put('/{transaction}', [TransactionController::class, 'update']);
        Route::patch('/{transaction}', [TransactionController::class, 'update']);
        Route::delete('/{transaction}', [TransactionController::class, 'destroy']);
    });

    Route::prefix('tax')->group(function () {
        Route::post('/calculate', [TaxCalculatorController::class, 'calculate']);
        Route::get('/rates', [TaxCalculatorController::class, 'getRates']);
        Route::post('/compare', [TaxCalculatorController::class, 'compareScenarios']);
        Route::post('/marginal-rate', [TaxCalculatorController::class, 'marginalRate']);
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/recent-transactions', [DashboardController::class, 'recentTransactions']);
    });
});
