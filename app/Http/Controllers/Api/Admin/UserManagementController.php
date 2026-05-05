<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManagedUserIndexRequest;
use App\Http\Requests\Admin\UpdateManagedUserPasswordRequest;
use App\Http\Requests\Admin\UpdateManagedUserRequest;
use App\Http\Requests\Admin\UpdateManagedUserStatusRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserManagementController extends Controller
{
    public function index(ManagedUserIndexRequest $request): JsonResponse
    {
        $actor = $request->user();
        $filters = $request->validated();

        $users = User::query()
            ->when($actor->role === 'admin', fn ($query) => $query
                ->where('role', '!=', 'admin')
                ->where('id', '!=', $actor->id))
            ->when($actor->role === 'manager', fn ($query) => $query->where('role', 'customer'))
            ->when(! empty($filters['name']), fn ($query) => $query->where('name', 'like', '%'.$filters['name'].'%'))
            ->when(! empty($filters['phone']), fn ($query) => $query->where('phone', 'like', '%'.$filters['phone'].'%'))
            ->when(! empty($filters['email']), fn ($query) => $query->where('email', 'like', '%'.$filters['email'].'%'))
            ->when(! empty($filters['role']), fn ($query) => $query->where('role', $filters['role']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest()
            ->get();

        return response()->json([
            'filters' => $filters,
            'users' => $users,
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureManageable($request->user(), $user);

        return response()->json([
            'user' => $user->load('addresses'),
        ]);
    }

    public function update(UpdateManagedUserRequest $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $user);

        $data = $request->validated();

        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
            'email' => array_key_exists('email', $data) ? $data['email'] : $user->email,
            'status' => $data['status'] ?? $user->status,
        ]);

        if ($actor->role === 'admin' && array_key_exists('role', $data)) {
            $user->role = $data['role'];
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    public function updatePassword(UpdateManagedUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->ensureManageable($request->user(), $user);

        $user->update([
            'password' => $request->string('password')->toString(),
        ]);

        $user->apiTokens()->delete();

        return response()->json([
            'message' => 'User password updated successfully.',
        ]);
    }

    public function updateStatus(UpdateManagedUserStatusRequest $request, User $user): JsonResponse
    {
        $this->ensureManageable($request->user(), $user);

        $user->update([
            'status' => $request->string('status')->toString(),
        ]);

        if ($user->status !== 'active') {
            $user->apiTokens()->delete();
        }

        return response()->json([
            'message' => 'User status updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    private function ensureManageable(User $actor, User $target): void
    {
        if ($actor->role === 'admin') {
            abort_if($target->role === 'admin', 403, 'You do not have permission to manage admin users from this route.');
            abort_if($target->id === $actor->id, 403, 'You cannot manage your own account from this route.');
            return;
        }

        if ($actor->id === $target->id) {
            abort(403, 'You cannot manage your own account from this route.');
        }

        abort_unless($actor->role === 'manager' && $target->role === 'customer', 403, 'You do not have permission to manage this user.');
    }
}
