<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MealOrderListRequest;
use App\Models\MealOrder;
use Illuminate\Http\JsonResponse;

class MealOrderController extends Controller
{
    public function index(MealOrderListRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $mealOrders = $request->user()
            ->mealOrders()
            ->with(['address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction'])
            ->when(! empty($filters['schedule_date']), fn ($query) => $query->whereDate('schedule_date', $filters['schedule_date']))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->where('meal_time', $filters['meal_time']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest('schedule_date')
            ->get();

        return response()->json([
            'filters' => $filters,
            'meal_orders' => $mealOrders,
        ]);
    }

    public function show(MealOrder $mealOrder): JsonResponse
    {
        abort_unless($mealOrder->user_id === request()->user()->id, 403, 'You do not have permission to access this meal order.');

        return response()->json([
            'meal_order' => $mealOrder->load(['address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction']),
        ]);
    }
}
