<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\NotificationListRequest;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(NotificationListRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->when(isset($filters['is_read']), fn ($query) => $query->where('is_read', $filters['is_read']))
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->latest()
            ->get();

        return response()->json([
            'filters' => $filters,
            'notifications' => $notifications,
        ]);
    }

    public function show(Request $request, Notification $notification): JsonResponse
    {
        $this->ensureOwnership($request, $notification);

        return response()->json([
            'notification' => $notification,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->ensureOwnership($request, $notification);

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => $this->notificationService->markAsRead($notification),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updatedCount = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'All notifications marked as read.',
            'updated_count' => $updatedCount,
        ]);
    }

    private function ensureOwnership(Request $request, Notification $notification): void
    {
        abort_unless($notification->user_id === $request->user()->id, 403, 'You do not have permission to access this notification.');
    }
}
