<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreAddressRequest;
use App\Http\Requests\Customer\UpdateAddressRequest;
use App\Models\Subarea;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'addresses' => $request->user()
                ->addresses()
                ->with('area')
                ->with('subarea')
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $this->ensureSubareaBelongsToArea($data['area_id'], $data['subarea_id']);

        if (! empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($data)->load(['area', 'subarea']);

        return response()->json([
            'message' => 'Address created successfully.',
            'address' => $address,
        ], 201);
    }

    public function show(Request $request, UserAddress $address): JsonResponse
    {
        $this->ensureOwnership($request, $address);

        return response()->json([
            'address' => $address->load(['area', 'subarea']),
        ]);
    }

    public function update(UpdateAddressRequest $request, UserAddress $address): JsonResponse
    {
        $this->ensureOwnership($request, $address);

        $data = $request->validated();
        $areaId = $data['area_id'] ?? $address->area_id;
        $subareaId = $data['subarea_id'] ?? $address->subarea_id;
        $this->ensureSubareaBelongsToArea($areaId, $subareaId);

        if (! empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'message' => 'Address updated successfully.',
            'address' => $address->fresh()->load(['area', 'subarea']),
        ]);
    }

    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        $this->ensureOwnership($request, $address);

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.',
        ]);
    }

    private function ensureOwnership(Request $request, UserAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403, 'You do not have permission to access this address.');
    }

    private function ensureSubareaBelongsToArea(int $areaId, int $subareaId): void
    {
        $exists = Subarea::query()
            ->where('id', $subareaId)
            ->where('area_id', $areaId)
            ->exists();

        abort_unless($exists, 422, 'Selected subarea does not belong to the selected area.');
    }
}
