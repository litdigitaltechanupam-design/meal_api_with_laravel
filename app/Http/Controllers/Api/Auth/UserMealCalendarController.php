<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserMealCalendarOverrideRequest;
use App\Http\Requests\Auth\UpdateUserMealCalendarOverrideRequest;
use App\Http\Requests\Auth\UserMealCalendarMonthRequest;
use App\Models\MealPackage;
use App\Models\UserMealCalendarOverride;
use App\Models\WeeklyMenuItem;
use App\Services\UserMealCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserMealCalendarController extends Controller
{
    public function __construct(private UserMealCalendarService $calendarService)
    {
    }

    public function weeklyMenu(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'day_of_week' => ['nullable', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
        ]);

        $items = WeeklyMenuItem::query()
            ->with('mealPackage')
            ->where('status', 'active')
            ->whereHas('mealPackage', fn ($query) => $query->where('status', 'active'))
            ->when(! empty($filters['day_of_week']), fn ($query) => $query->where('day_of_week', $filters['day_of_week']))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->where('meal_time', $filters['meal_time']))
            ->orderByRaw("FIELD(day_of_week, 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')")
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')")
            ->get();

        return response()->json([
            'weekly_menu_items' => $items,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $query = $request->user()
            ->mealCalendarOverrides()
            ->with('mealPackage')
            ->orderBy('schedule_date')
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')");

        if (! empty($filters['month'])) {
            $start = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $query->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()]);
        }

        return response()->json([
            'calendar_overrides' => $query->get(),
        ]);
    }

    public function store(StoreUserMealCalendarOverrideRequest $request): JsonResponse
    {
        $data = $this->prepareOverrideData($request->validated());
        $this->ensureAllowedDateSelection($data['schedule_date'], $data['meal_time'], $data['meal_package_id'], $data['is_off']);

        $override = UserMealCalendarOverride::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'schedule_date' => $data['schedule_date'],
                'meal_time' => $data['meal_time'],
            ],
            [
                'meal_package_id' => $data['meal_package_id'],
                'is_off' => $data['is_off'],
            ]
        );

        return response()->json([
            'message' => 'Calendar override saved successfully.',
            'calendar_override' => $override->load('mealPackage'),
        ], 201);
    }

    public function update(UpdateUserMealCalendarOverrideRequest $request, UserMealCalendarOverride $calendarOverride): JsonResponse
    {
        $this->ensureOwnership($request, $calendarOverride);

        $data = $this->prepareOverrideData(array_merge($calendarOverride->only(['schedule_date', 'meal_time']), $request->validated()));
        $this->ensureAllowedDateSelection($data['schedule_date'], $data['meal_time'], $data['meal_package_id'], $data['is_off']);

        $calendarOverride->update([
            'schedule_date' => $data['schedule_date'],
            'meal_time' => $data['meal_time'],
            'meal_package_id' => $data['meal_package_id'],
            'is_off' => $data['is_off'],
        ]);

        return response()->json([
            'message' => 'Calendar override updated successfully.',
            'calendar_override' => $calendarOverride->fresh()->load('mealPackage'),
        ]);
    }

    public function destroy(Request $request, UserMealCalendarOverride $calendarOverride): JsonResponse
    {
        $this->ensureOwnership($request, $calendarOverride);
        $calendarOverride->delete();

        return response()->json([
            'message' => 'Calendar override deleted successfully.',
        ]);
    }

    public function month(UserMealCalendarMonthRequest $request): JsonResponse
    {
        return response()->json(
            $this->calendarService->buildMonth($request->user(), $request->string('month')->toString())
        );
    }

    private function prepareOverrideData(array $data): array
    {
        $isOff = (bool) ($data['is_off'] ?? false);

        return [
            'schedule_date' => Carbon::parse($data['schedule_date'])->toDateString(),
            'meal_time' => $data['meal_time'],
            'meal_package_id' => $isOff ? null : ($data['meal_package_id'] ?? null),
            'is_off' => $isOff,
        ];
    }

    private function ensureAllowedDateSelection(string $scheduleDate, string $mealTime, ?int $mealPackageId, bool $isOff): void
    {
        if ($isOff) {
            return;
        }

        abort_if(! $mealPackageId, 422, 'Meal package is required when meal is on.');

        $package = MealPackage::query()->findOrFail($mealPackageId);
        abort_if($package->status !== 'active', 422, 'Inactive package cannot be selected.');

        $dayOfWeek = strtolower(Carbon::parse($scheduleDate)->englishDayOfWeek);

        $available = WeeklyMenuItem::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('meal_time', $mealTime)
            ->where('meal_package_id', $mealPackageId)
            ->where('status', 'active')
            ->exists();

        abort_if(! $available, 422, 'Selected package is not available on this date and meal time.');
    }

    private function ensureOwnership(Request $request, UserMealCalendarOverride $calendarOverride): void
    {
        abort_unless($calendarOverride->user_id === $request->user()->id, 403, 'You do not have permission to access this calendar override.');
    }
}
