<?php

namespace App\Http\Controllers\Api\Deliveryman;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deliveryman\DeliveryListRequest;
use App\Http\Requests\Deliveryman\UpdateDeliveryStatusRequest;
use App\Models\Delivery;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(DeliveryListRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $deliveries = $request->user()
            ->deliveries()
            ->with(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage'])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['schedule_date']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', $filters['schedule_date'])))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->where('meal_time', $filters['meal_time'])))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '>=', $filters['date_from'])))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '<=', $filters['date_to'])))
            ->latest()
            ->get();

        return response()->json([
            'filters' => $filters,
            'deliveries' => $deliveries,
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $deliveries = $request->user()
            ->deliveries()
            ->with(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage'])
            ->whereHas('mealOrder', fn ($query) => $query->whereDate('schedule_date', $today))
            ->latest()
            ->get();

        return response()->json([
            'schedule_date' => $today,
            'deliveries' => $deliveries,
        ]);
    }

    public function show(Request $request, Delivery $delivery): JsonResponse
    {
        $this->ensureOwnership($request, $delivery);

        return response()->json([
            'delivery' => $delivery->load(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage']),
        ]);
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery): JsonResponse
    {
        $this->ensureOwnership($request, $delivery);

        $nextStatus = $request->validated('status');
        $this->ensureTransitionAllowed($delivery->status, $nextStatus);

        $payload = [
            'status' => $nextStatus,
            'note' => $request->validated('note') ?? $delivery->note,
        ];

        if ($nextStatus === 'picked') {
            $payload['picked_at'] = now();
            $delivery->mealOrder()->update(['status' => 'out_for_delivery']);
        }

        if ($nextStatus === 'delivered') {
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

        if ($nextStatus === 'failed') {
            $payload['failed_at'] = now();
            $delivery->mealOrder()->update(['status' => 'failed']);
        }

        $delivery->update($payload);

        return response()->json([
            'message' => 'Delivery status updated successfully.',
            'delivery' => $delivery->fresh()->load(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage']),
        ]);
    }

    private function ensureOwnership(Request $request, Delivery $delivery): void
    {
        abort_unless($delivery->deliveryman_id === $request->user()->id, 403, 'You do not have permission to access this delivery.');
    }

    private function ensureTransitionAllowed(string $currentStatus, string $nextStatus): void
    {
        $allowed = [
            'assigned' => ['picked', 'failed'],
            'picked' => ['delivered', 'failed'],
            'delivered' => [],
            'failed' => [],
        ];

        abort_if(! in_array($nextStatus, $allowed[$currentStatus] ?? [], true), 422, 'This delivery status change is not allowed.');
    }
}
