<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserCalendarOverrideRequest;
use App\Http\Requests\Auth\UpdateUserCalendarOverrideRequest;
use App\Http\Requests\Auth\UserCalendarMonthRequest;
use App\Models\MealPackage;
use App\Models\UserCalendarOverride;
use App\Models\WeeklyMenu;
use App\Services\UserMealCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserCalendarController extends Controller
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
            ->with('items.mealPackage')
            ->orderBy('schedule_date')
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')");

        if (! empty($filters['month'])) {
            $start = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $query->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()]);
        }

        return response()->json(['user_calendar_overrides' => $query->get()]);
    }

    public function store(StoreUserCalendarOverrideRequest $request): JsonResponse
    {
        $payload = $this->normalizePayload($request->validated());
        $this->ensureAllowedSelection($payload['schedule_date'], $payload['meal_time'], $payload['is_off'], $payload['items']);

        $override = DB::transaction(function () use ($request, $payload) {
            $override = UserCalendarOverride::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'schedule_date' => $payload['schedule_date'],
                    'meal_time' => $payload['meal_time'],
                ],
                [
                    'is_off' => $payload['is_off'],
                ]
            );

            $override->items()->delete();
            if (! $payload['is_off']) {
                $override->items()->createMany($payload['items']);
            }

            return $override->load('items.mealPackage');
        });

        return response()->json([
            'message' => 'Calendar override saved successfully.',
            'user_calendar_override' => $override,
        ], 201);
    }

    public function update(UpdateUserCalendarOverrideRequest $request, UserCalendarOverride $userCalendarOverride): JsonResponse
    {
        $this->ensureOwnership($request, $userCalendarOverride);

        $payload = $this->normalizePayload(array_merge(
            [
                'schedule_date' => $userCalendarOverride->schedule_date->toDateString(),
                'meal_time' => $userCalendarOverride->meal_time,
                'is_off' => $userCalendarOverride->is_off,
            ],
            $request->validated()
        ));
        $this->ensureAllowedSelection($payload['schedule_date'], $payload['meal_time'], $payload['is_off'], $payload['items']);

        DB::transaction(function () use ($userCalendarOverride, $payload) {
            $userCalendarOverride->update([
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
            'user_calendar_override' => $userCalendarOverride->fresh()->load('items.mealPackage'),
        ]);
    }

    public function destroy(Request $request, UserCalendarOverride $userCalendarOverride): JsonResponse
    {
        $this->ensureOwnership($request, $userCalendarOverride);
        $userCalendarOverride->delete();

        return response()->json(['message' => 'Calendar override deleted successfully.']);
    }

    public function monthSummary(UserCalendarMonthRequest $request): JsonResponse
    {
        return response()->json(
            $this->calendarService->buildMonth($request->user(), $request->string('month')->toString())
        );
    }

    private function normalizePayload(array $data): array
    {
        $isOff = (bool) ($data['is_off'] ?? false);

        return [
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
}
