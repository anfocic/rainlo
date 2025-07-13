<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected StatsService $statsService;

    public function __construct(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $data = $this->statsService->getDashboardSummary(auth()->id());

            return $this->successWithData($data, 'Dashboard summary retrieved successfully', 200, [
                'cached' => true
            ]);
        });
    }

    public function recentTransactions(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $limit = $request->get('limit', 10);
            $transactions = $this->statsService->getRecentTransactions(auth()->id(), $limit);

            return $this->successWithData(
                $transactions,
                'Recent transactions retrieved successfully',
                200,
                [
                    'limit' => $limit,
                    'total_count' => count($transactions),
                    'cached' => true
                ]
            );
        });
    }

    public function user(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $user = $request->user();

            return $this->successWithData($user, 'User information retrieved successfully');
        });
    }
}
