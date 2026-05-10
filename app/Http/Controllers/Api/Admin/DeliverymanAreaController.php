<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDeliverymanAreaRequest;
use App\Http\Requests\Admin\UpdateDeliverymanAreaRequest;
use App\Models\DeliverymanArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliverymanAreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'deliveryman_id' => ['nullable', 'integer', 'exists:users,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $deliverymanAreas = DeliverymanArea::query()
            ->with(['deliveryman:id,name,phone,email,role,status', 'area'])
            ->when(! empty($filters['deliveryman_id']), fn ($query) => $query->where('deliveryman_id', $filters['deliveryman_id']))
            ->when(! empty($filters['area_id']), fn ($query) => $query->where('area_id', $filters['area_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->get();

        return response()->json(['deliveryman_areas' => $deliverymanAreas]);
    }

    public function store(StoreDeliverymanAreaRequest $request): JsonResponse
    {
        $deliverymanArea = DeliverymanArea::updateOrCreate(
            [
                'deliveryman_id' => $request->integer('deliveryman_id'),
                'area_id' => $request->integer('area_id'),
            ],
            [
                'status' => $request->input('status', 'active'),
            ]
        )->load(['deliveryman:id,name,phone,email,role,status', 'area']);

        return response()->json([
            'message' => 'Deliveryman area saved successfully.',
            'deliveryman_area' => $deliverymanArea,
        ], 201);
    }

    public function update(UpdateDeliverymanAreaRequest $request, DeliverymanArea $deliverymanArea): JsonResponse
    {
        $deliverymanArea->update($request->validated());

        return response()->json([
            'message' => 'Deliveryman area updated successfully.',
            'deliveryman_area' => $deliverymanArea->fresh()->load(['deliveryman:id,name,phone,email,role,status', 'area']),
        ]);
    }

    public function destroy(DeliverymanArea $deliverymanArea): JsonResponse
    {
        $deliverymanArea->delete();

        return response()->json([
            'message' => 'Deliveryman area removed successfully.',
        ]);
    }
}
