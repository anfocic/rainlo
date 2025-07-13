<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Tax\TaxCalculatorController;
use App\Http\Controllers\Transaction\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Health check endpoint
Route::get('/health', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'app' => 'Rainlo API',
            'version' => '1.0.0',
            'services' => [
                'database' => 'connected',
                'application' => 'running'
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => now()->toISOString(),
            'app' => 'Rainlo API',
            'version' => '1.0.0',
            'error' => 'Database connection failed',
            'services' => [
                'database' => 'disconnected',
                'application' => 'running'
            ]
        ], 503);
    }
});

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
    Route::get('/user', [DashboardController::class, 'user']);

    /**
     * AUTHENTICATION
     */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', function (Request $request) {
            return response()->json(['data' => $request->user()]);
        });
    });

    /**
     * TRANSACTIONS
     */
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

    /**
     * TAX
     */
    Route::prefix('tax')->group(function () {
        Route::post('/calculate', [TaxCalculatorController::class, 'calculate']);
        Route::get('/rates', [TaxCalculatorController::class, 'getRates']);
        Route::post('/compare', [TaxCalculatorController::class, 'compareScenarios']);
        Route::post('/marginal-rate', [TaxCalculatorController::class, 'marginalRate']);
    });

    /**
     * DASHBOARD
     */
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/recent-transactions', [DashboardController::class, 'recentTransactions']);
    });
});
