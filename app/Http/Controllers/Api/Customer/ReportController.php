<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function mealOrders(CustomerReportRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportService->customerMealOrdersReport($request->user(), $request->validated())
        );
    }

    public function wallets(CustomerReportRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportService->customerWalletReport($request->user(), $request->validated())
        );
    }

    public function refunds(CustomerReportRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportService->customerRefundReport($request->user(), $request->validated())
        );
    }
}
