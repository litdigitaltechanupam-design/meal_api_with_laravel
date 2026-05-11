<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryIndexRequest;
use App\Http\Requests\Admin\MealOrderReportRequest;
use App\Http\Requests\Admin\RefundReportRequest;
use App\Http\Requests\Admin\WalletReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function mealOrders(MealOrderReportRequest $request): JsonResponse
    {
        return response()->json($this->reportService->mealOrderReport($request->validated()));
    }

    public function deliveries(DeliveryIndexRequest $request): JsonResponse
    {
        return response()->json($this->reportService->deliveryReport($request->validated()));
    }

    public function wallets(WalletReportRequest $request): JsonResponse
    {
        return response()->json($this->reportService->walletReport($request->validated()));
    }

    public function refunds(RefundReportRequest $request): JsonResponse
    {
        return response()->json($this->reportService->refundReport($request->validated()));
    }
}
