<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryIndexRequest;
use App\Http\Requests\Admin\UpdateDeliveryStatusRequest;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;

class DeliveryController extends Controller
{
    public function index(DeliveryIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $deliveries = Delivery::query()
            ->with(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage', 'deliveryman'])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['deliveryman_id']), fn ($query) => $query->where('deliveryman_id', $filters['deliveryman_id']))
            ->when(! empty($filters['schedule_date']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', $filters['schedule_date'])))
            ->latest()
            ->get();

        return response()->json([
            'filters' => $filters,
            'deliveries' => $deliveries,
        ]);
    }

    public function show(Delivery $delivery): JsonResponse
    {
        return response()->json([
            'delivery' => $delivery->load(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage', 'deliveryman']),
        ]);
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery): JsonResponse
    {
        $data = $request->validated();
        $status = $data['status'];

        $payload = [
            'status' => $status,
            'note' => $data['note'] ?? $delivery->note,
        ];

        if ($status === 'picked') {
            $payload['picked_at'] = now();
            $delivery->mealOrder()->update(['status' => 'out_for_delivery']);
        }

        if ($status === 'delivered') {
            $payload['delivered_at'] = now();
            $delivery->mealOrder()->update(['status' => 'delivered']);
        }

        if ($status === 'failed') {
            $payload['failed_at'] = now();
            $delivery->mealOrder()->update(['status' => 'failed']);
        }

        if ($status === 'assigned') {
            $payload['assigned_at'] = $delivery->assigned_at ?: now();
        }

        $delivery->update($payload);

        return response()->json([
            'message' => 'Delivery status updated successfully.',
            'delivery' => $delivery->fresh()->load(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage', 'deliveryman']),
        ]);
    }
}
