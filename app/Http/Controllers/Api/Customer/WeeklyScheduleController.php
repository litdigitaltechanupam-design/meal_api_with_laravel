<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreWeeklyScheduleRequest;
use App\Http\Requests\Customer\UpdateWeeklyScheduleRequest;
use App\Models\MealPackage;
use App\Models\UserAddress;
use App\Models\UserWeeklySchedule;
use App\Models\WeeklyMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schedules = $request->user()
            ->userWeeklySchedules()
            ->with(['address.area', 'items.mealPackage'])
            ->orderByRaw("FIELD(day_of_week, 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')")
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')")
            ->get();

        return response()->json(['user_weekly_schedules' => $schedules]);
    }

    public function store(StoreWeeklyScheduleRequest $request): JsonResponse
    {
        $payload = $this->normalizePayload($request->validated());
        $this->ensureAddressOwnership($request, $payload['address_id'], $payload['is_off']);
        $this->ensureAllowedSelection($payload['day_of_week'], $payload['meal_time'], $payload['is_off'], $payload['items']);

        $schedule = DB::transaction(function () use ($request, $payload) {
            $schedule = UserWeeklySchedule::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'day_of_week' => $payload['day_of_week'],
                    'meal_time' => $payload['meal_time'],
                ],
                [
                    'address_id' => $payload['address_id'],
                    'is_off' => $payload['is_off'],
                ]
            );

            $schedule->items()->delete();
            if (! $payload['is_off']) {
                $schedule->items()->createMany($payload['items']);
            }

            return $schedule->load(['address.area', 'items.mealPackage']);
        });

        return response()->json([
            'message' => 'Weekly schedule saved successfully.',
            'user_weekly_schedule' => $schedule,
        ], 201);
    }

    public function update(UpdateWeeklyScheduleRequest $request, UserWeeklySchedule $userWeeklySchedule): JsonResponse
    {
        $this->ensureOwnership($request, $userWeeklySchedule);

        $payload = $this->normalizePayload(array_merge(
            $userWeeklySchedule->only(['day_of_week', 'meal_time', 'is_off', 'address_id']),
            $request->validated()
        ));
        $this->ensureAddressOwnership($request, $payload['address_id'], $payload['is_off']);
        $this->ensureAllowedSelection($payload['day_of_week'], $payload['meal_time'], $payload['is_off'], $payload['items']);

        DB::transaction(function () use ($userWeeklySchedule, $payload) {
            $userWeeklySchedule->update([
                'address_id' => $payload['address_id'],
                'day_of_week' => $payload['day_of_week'],
                'meal_time' => $payload['meal_time'],
                'is_off' => $payload['is_off'],
            ]);

            $userWeeklySchedule->items()->delete();
            if (! $payload['is_off']) {
                $userWeeklySchedule->items()->createMany($payload['items']);
            }
        });

        return response()->json([
            'message' => 'Weekly schedule updated successfully.',
            'user_weekly_schedule' => $userWeeklySchedule->fresh()->load(['address.area', 'items.mealPackage']),
        ]);
    }

    private function normalizePayload(array $data): array
    {
        $isOff = (bool) ($data['is_off'] ?? false);

        return [
            'address_id' => $isOff ? null : ($data['address_id'] ?? null),
            'day_of_week' => $data['day_of_week'],
            'meal_time' => $data['meal_time'],
            'is_off' => $isOff,
            'items' => $isOff ? [] : collect($data['items'] ?? [])->map(fn ($item) => [
                'meal_package_id' => (int) $item['meal_package_id'],
                'quantity' => (int) $item['quantity'],
            ])->values()->all(),
        ];
    }

    private function ensureAllowedSelection(string $dayOfWeek, string $mealTime, bool $isOff, array $items): void
    {
        if ($isOff) {
            return;
        }

        abort_if(empty($items), 422, 'At least one meal item is required when meal is on.');

        $availablePackageIds = WeeklyMenu::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('meal_time', $mealTime)
            ->where('status', 'active')
            ->with(['items' => fn ($query) => $query->select('id', 'weekly_menu_id', 'meal_package_id')])
            ->first()?->items
            ->pluck('meal_package_id')
            ->all() ?? [];

        foreach ($items as $item) {
            $package = MealPackage::query()->findOrFail($item['meal_package_id']);
            abort_if($package->status !== 'active', 422, 'Inactive package cannot be selected.');
            abort_if(! in_array($item['meal_package_id'], $availablePackageIds, true), 422, 'Selected package is not available for this day and meal time.');
        }
    }

    private function ensureOwnership(Request $request, UserWeeklySchedule $userWeeklySchedule): void
    {
        abort_unless($userWeeklySchedule->user_id === $request->user()->id, 403, 'You do not have permission to access this weekly schedule.');
    }

    private function ensureAddressOwnership(Request $request, ?int $addressId, bool $isOff): void
    {
        if ($isOff) {
            return;
        }

        abort_if(empty($addressId), 422, 'Address is required when meal is on.');

        $addressExists = UserAddress::query()
            ->where('id', $addressId)
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_unless($addressExists, 422, 'Selected address does not belong to this customer.');
    }
}
