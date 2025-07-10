<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*

*/

Route::get('/', function () {
    return response()->json([
        'message' => 'SmartTax API is running',
        'version' => '1.0.0',
        'status' => 'healthy'
    ]);
});

// Health check endpoint for deployment monitoring
Route::get('/up', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => 'connected',
                'application' => 'running'
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => now()->toISOString(),
            'error' => 'Database connection failed',
            'services' => [
                'database' => 'disconnected',
                'application' => 'running'
            ]
        ], 503);
    }
});
