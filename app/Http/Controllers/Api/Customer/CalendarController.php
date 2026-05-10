<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCalendarOverrideRequest;
use App\Http\Requests\Customer\UpdateCalendarOverrideRequest;
use App\Http\Requests\Customer\CalendarMonthRequest;
use App\Models\MealPackage;
use App\Models\UserAddress;
use App\Models\UserCalendarOverride;
use App\Models\WeeklyMenu;
use App\Services\UserMealCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function __construct(private UserMealCalendarService $calendarService)
    {
    }

    public function weeklyMenus(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'day_of_week' => ['nullable', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
        ]);

        $weeklyMenus = WeeklyMenu::query()
            ->with('items.mealPackage')
            ->where('status', 'active')
            ->when(! empty($filters['day_of_week']), fn ($query) => $query->where('day_of_week', $filters['day_of_week']))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->where('meal_time', $filters['meal_time']))
            ->orderByRaw("FIELD(day_of_week, 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')")
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')")
            ->get();

        return response()->json(['weekly_menus' => $weeklyMenus]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $query = $request->user()
            ->userCalendarOverrides()
            ->with(['address.area', 'items.mealPackage'])
            ->orderBy('schedule_date')
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')");

        if (! empty($filters['month'])) {
            $start = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $query->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()]);
        }

        return response()->json(['user_calendar_overrides' => $query->get()]);
    }

    public function store(StoreCalendarOverrideRequest $request): JsonResponse
    {
        $payload = $this->normalizePayload($request->validated());
        $this->ensureAddressOwnership($request, $payload['address_id'], $payload['is_off']);
        $this->ensureAllowedSelection($payload['schedule_date'], $payload['meal_time'], $payload['is_off'], $payload['items']);

        $override = DB::transaction(function () use ($request, $payload) {
            $override = UserCalendarOverride::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'schedule_date' => $payload['schedule_date'],
                    'meal_time' => $payload['meal_time'],
                ],
                [
                    'address_id' => $payload['address_id'],
                    'is_off' => $payload['is_off'],
                ]
            );

            $override->items()->delete();
            if (! $payload['is_off']) {
                $override->items()->createMany($payload['items']);
            }

            return $override->load(['address.area', 'items.mealPackage']);
        });

        return response()->json([
            'message' => 'Calendar override saved successfully.',
            'user_calendar_override' => $override,
        ], 201);
    }

    public function update(UpdateCalendarOverrideRequest $request, UserCalendarOverride $userCalendarOverride): JsonResponse
    {
        $this->ensureOwnership($request, $userCalendarOverride);

        $payload = $this->normalizePayload(array_merge(
            [
                'schedule_date' => $userCalendarOverride->schedule_date->toDateString(),
                'meal_time' => $userCalendarOverride->meal_time,
                'is_off' => $userCalendarOverride->is_off,
                'address_id' => $userCalendarOverride->address_id,
            ],
            $request->validated()
        ));
        $this->ensureAddressOwnership($request, $payload['address_id'], $payload['is_off']);
        $this->ensureAllowedSelection($payload['schedule_date'], $payload['meal_time'], $payload['is_off'], $payload['items']);

        DB::transaction(function () use ($userCalendarOverride, $payload) {
            $userCalendarOverride->update([
                'address_id' => $payload['address_id'],
                'schedule_date' => $payload['schedule_date'],
                'meal_time' => $payload['meal_time'],
                'is_off' => $payload['is_off'],
            ]);

            $userCalendarOverride->items()->delete();
            if (! $payload['is_off']) {
                $userCalendarOverride->items()->createMany($payload['items']);
            }
        });

        return response()->json([
            'message' => 'Calendar override updated successfully.',
            'user_calendar_override' => $userCalendarOverride->fresh()->load(['address.area', 'items.mealPackage']),
        ]);
    }

    public function destroy(Request $request, UserCalendarOverride $userCalendarOverride): JsonResponse
    {
        $this->ensureOwnership($request, $userCalendarOverride);
        $userCalendarOverride->delete();

        return response()->json(['message' => 'Calendar override deleted successfully.']);
    }

    public function monthSummary(CalendarMonthRequest $request): JsonResponse
    {
        return response()->json(
            $this->calendarService->buildMonth($request->user(), $request->string('month')->toString())
        );
    }

    private function normalizePayload(array $data): array
    {
        $isOff = (bool) ($data['is_off'] ?? false);

        return [
            'address_id' => $isOff ? null : ($data['address_id'] ?? null),
            'schedule_date' => Carbon::parse($data['schedule_date'])->toDateString(),
            'meal_time' => $data['meal_time'],
            'is_off' => $isOff,
            'items' => $isOff ? [] : collect($data['items'] ?? [])->map(fn ($item) => [
                'meal_package_id' => (int) $item['meal_package_id'],
                'quantity' => (int) $item['quantity'],
            ])->values()->all(),
        ];
    }

    private function ensureAllowedSelection(string $scheduleDate, string $mealTime, bool $isOff, array $items): void
    {
        if ($isOff) {
            return;
        }

        abort_if(empty($items), 422, 'At least one meal item is required when meal is on.');

        $dayOfWeek = strtolower(Carbon::parse($scheduleDate)->englishDayOfWeek);

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
            abort_if(! in_array($item['meal_package_id'], $availablePackageIds, true), 422, 'Selected package is not available on this date and meal time.');
        }
    }

    private function ensureOwnership(Request $request, UserCalendarOverride $userCalendarOverride): void
    {
        abort_unless($userCalendarOverride->user_id === $request->user()->id, 403, 'You do not have permission to access this calendar override.');
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
