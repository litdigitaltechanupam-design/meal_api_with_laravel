<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserAddressRequest;
use App\Http\Requests\Auth\UpdateUserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'addresses' => $request->user()
                ->addresses()
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreUserAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($data);

        return response()->json([
            'message' => 'Address created successfully.',
            'address' => $address,
        ], 201);
    }

    public function show(Request $request, UserAddress $address): JsonResponse
    {
        $this->ensureOwnership($request, $address);

        return response()->json([
            'address' => $address,
        ]);
    }

    public function update(UpdateUserAddressRequest $request, UserAddress $address): JsonResponse
    {
        $this->ensureOwnership($request, $address);

        $data = $request->validated();

        if (! empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'message' => 'Address updated successfully.',
            'address' => $address->fresh(),
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
}
