<?php

namespace App\Http\Controllers\Api\Deliveryman;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deliveryman\DeliveryReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function deliveries(DeliveryReportRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportService->deliverymanDeliveriesReport($request->user(), $request->validated())
        );
    }
}
