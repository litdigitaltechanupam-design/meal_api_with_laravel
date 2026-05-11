<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'dashboard' => $this->reportService->customerDashboard($request->user()),
        ]);
    }
}
