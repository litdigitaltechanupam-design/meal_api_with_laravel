<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDeliverymanSubareaRequest;
use App\Http\Requests\Admin\UpdateDeliverymanSubareaRequest;
use App\Models\DeliverymanSubarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliverymanSubareaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'deliveryman_id' => ['nullable', 'integer', 'exists:users,id'],
            'subarea_id' => ['nullable', 'integer', 'exists:subareas,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $deliverymanSubareas = DeliverymanSubarea::query()
            ->with(['deliveryman:id,name,phone,email,role,status', 'subarea.area'])
            ->when(! empty($filters['deliveryman_id']), fn ($query) => $query->where('deliveryman_id', $filters['deliveryman_id']))
            ->when(! empty($filters['subarea_id']), fn ($query) => $query->where('subarea_id', $filters['subarea_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->get();

        return response()->json(['deliveryman_subareas' => $deliverymanSubareas]);
    }

    public function store(StoreDeliverymanSubareaRequest $request): JsonResponse
    {
        $deliverymanSubarea = DeliverymanSubarea::updateOrCreate(
            [
                'deliveryman_id' => $request->integer('deliveryman_id'),
                'subarea_id' => $request->integer('subarea_id'),
            ],
            [
                'status' => $request->input('status', 'active'),
            ]
        )->load(['deliveryman:id,name,phone,email,role,status', 'subarea.area']);

        return response()->json([
            'message' => 'Deliveryman subarea saved successfully.',
            'deliveryman_subarea' => $deliverymanSubarea,
        ], 201);
    }

    public function update(UpdateDeliverymanSubareaRequest $request, DeliverymanSubarea $deliverymanSubarea): JsonResponse
    {
        $deliverymanSubarea->update($request->validated());

        return response()->json([
            'message' => 'Deliveryman subarea updated successfully.',
            'deliveryman_subarea' => $deliverymanSubarea->fresh()->load(['deliveryman:id,name,phone,email,role,status', 'subarea.area']),
        ]);
    }

    public function destroy(DeliverymanSubarea $deliverymanSubarea): JsonResponse
    {
        $deliverymanSubarea->delete();

        return response()->json([
            'message' => 'Deliveryman subarea removed successfully.',
        ]);
    }
}
