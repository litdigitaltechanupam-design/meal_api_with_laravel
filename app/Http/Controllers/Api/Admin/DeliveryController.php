<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignDeliveryRequest;
use App\Http\Requests\Admin\DeliveryIndexRequest;
use App\Http\Requests\Admin\UpdateDeliveryStatusRequest;
use App\Models\Delivery;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class DeliveryController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(DeliveryIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $deliveries = Delivery::query()
            ->with(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage', 'deliveryman'])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['deliveryman_id']), fn ($query) => $query->where('deliveryman_id', $filters['deliveryman_id']))
            ->when(! empty($filters['schedule_date']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', $filters['schedule_date'])))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->where('meal_time', $filters['meal_time'])))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '>=', $filters['date_from'])))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '<=', $filters['date_to'])))
            ->when(! empty($filters['user_id']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->where('user_id', $filters['user_id'])))
            ->when(! empty($filters['phone']), fn ($query) => $query->whereHas('mealOrder.user', fn ($userQuery) => $userQuery->where('phone', 'like', '%'.$filters['phone'].'%')))
            ->when(! empty($filters['area_id']), fn ($query) => $query->whereHas('mealOrder.address', fn ($addressQuery) => $addressQuery->where('area_id', $filters['area_id'])))
            ->when(! empty($filters['subarea_id']), fn ($query) => $query->whereHas('mealOrder.address', fn ($addressQuery) => $addressQuery->where('subarea_id', $filters['subarea_id'])))
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
            $this->notificationService->sendToUser(
                $delivery->mealOrder->user,
                'delivery_delivered',
                'Delivery Completed',
                'আপনার '.$delivery->mealOrder->meal_time.' delivery সম্পন্ন হয়েছে।',
                [
                    'delivery_id' => $delivery->id,
                    'meal_order_id' => $delivery->meal_order_id,
                ]
            );
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

    public function assign(AssignDeliveryRequest $request, Delivery $delivery): JsonResponse
    {
        $deliveryman = User::query()
            ->where('id', $request->validated('deliveryman_id'))
            ->where('role', 'deliveryman')
            ->where('status', 'active')
            ->first();

        abort_if(! $deliveryman, 422, 'Selected deliveryman is invalid.');

        $delivery->update([
            'deliveryman_id' => $deliveryman->id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'note' => $request->validated('note') ?? 'Deliveryman assigned manually by management.',
        ]);

        $this->notificationService->sendToUser(
            $deliveryman,
            'delivery_batch_assigned',
            ucfirst($delivery->mealOrder->meal_time).' Delivery Assigned',
            'আজকের '.ucfirst($delivery->mealOrder->meal_time).' এর জন্য নতুন delivery assign হয়েছে।',
            [
                'delivery_id' => $delivery->id,
                'meal_order_id' => $delivery->meal_order_id,
                'schedule_date' => $delivery->mealOrder->schedule_date->toDateString(),
                'meal_time' => $delivery->mealOrder->meal_time,
            ]
        );

        return response()->json([
            'message' => 'Delivery assigned successfully.',
            'delivery' => $delivery->fresh()->load(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage', 'deliveryman']),
        ]);
    }
}
