<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateMealOrdersRequest;
use App\Http\Requests\Admin\MealOrderIndexRequest;
use App\Http\Requests\Admin\StoreMealOrderRefundRequest;
use App\Http\Requests\Admin\UpdateMealOrderStatusRequest;
use App\Models\MealOrder;
use App\Services\MealOrderService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class MealOrderController extends Controller
{
    public function __construct(
        private MealOrderService $mealOrderService,
        private WalletService $walletService,
    )
    {
    }

    public function index(MealOrderIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $mealOrders = MealOrder::query()
            ->with(['user', 'address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction'])
            ->when(! empty($filters['schedule_date']), fn ($query) => $query->whereDate('schedule_date', $filters['schedule_date']))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->where('meal_time', $filters['meal_time']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->latest('schedule_date')
            ->get();

        return response()->json([
            'filters' => $filters,
            'meal_orders' => $mealOrders,
        ]);
    }

    public function generate(GenerateMealOrdersRequest $request): JsonResponse
    {
        $result = $this->mealOrderService->generateForDate(
            $request->validated('schedule_date'),
            $request->user(),
        );

        return response()->json([
            'message' => 'Meal orders generated successfully.',
            'schedule_date' => $request->validated('schedule_date'),
            'generated_count' => count($result['generated']),
            'skipped_count' => count($result['skipped']),
            'generated_orders' => $result['generated'],
            'skipped' => $result['skipped'],
        ], 201);
    }

    public function show(MealOrder $mealOrder): JsonResponse
    {
        return response()->json([
            'meal_order' => $mealOrder->load(['user', 'address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction']),
        ]);
    }

    public function updateStatus(UpdateMealOrderStatusRequest $request, MealOrder $mealOrder): JsonResponse
    {
        $mealOrder->update($request->validated());

        return response()->json([
            'message' => 'Meal order status updated successfully.',
            'meal_order' => $mealOrder->fresh()->load(['user', 'address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction']),
        ]);
    }

    public function refund(StoreMealOrderRefundRequest $request, MealOrder $mealOrder): JsonResponse
    {
        abort_if(! $mealOrder->is_wallet_deducted, 422, 'This order has no wallet deduction to refund.');
        abort_if($mealOrder->is_refunded, 422, 'This order has already been refunded.');

        $wallet = $this->walletService->getOrCreateWallet($mealOrder->user);
        $transaction = $this->walletService->credit($wallet, [
            'type' => 'refund',
            'amount' => $mealOrder->total_amount,
            'reference_type' => 'meal_order',
            'reference_id' => $mealOrder->id,
            'payment_method' => 'system',
            'status' => 'completed',
            'note' => $request->validated('note'),
            'created_by' => $request->user()->id,
        ]);

        $mealOrder->update([
            'is_refunded' => true,
            'refunded_at' => now(),
            'note' => $request->validated('note'),
        ]);

        return response()->json([
            'message' => 'Meal order refunded successfully.',
            'meal_order' => $mealOrder->fresh()->load(['user', 'address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction']),
            'wallet_transaction' => $transaction,
        ], 201);
    }
}
