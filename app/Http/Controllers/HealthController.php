<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        return $this->executeWithErrorHandling(function () {
            DB::connection()->getPdo();

            return $this->successWithData([
                'app' => 'Rainlo API',
                'version' => '1.0.0',
                'services' => [
                    'database' => 'connected',
                    'application' => 'running'
                ]
            ], 'System is healthy');
        });
    }
}
