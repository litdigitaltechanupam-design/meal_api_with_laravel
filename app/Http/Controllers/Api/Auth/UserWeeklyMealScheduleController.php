<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserWeeklyMealScheduleRequest;
use App\Http\Requests\Auth\UpdateUserWeeklyMealScheduleRequest;
use App\Models\MealPackage;
use App\Models\UserWeeklyMealSchedule;
use App\Models\WeeklyMenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserWeeklyMealScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schedules = $request->user()
            ->weeklyMealSchedules()
            ->with('mealPackage')
            ->orderByRaw("FIELD(day_of_week, 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')")
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')")
            ->get();

        return response()->json([
            'weekly_schedules' => $schedules,
        ]);
    }

    public function store(StoreUserWeeklyMealScheduleRequest $request): JsonResponse
    {
        $data = $this->prepareScheduleData($request->validated());
        $this->ensureAllowedWeeklySelection($data['day_of_week'], $data['meal_time'], $data['meal_package_id'], $data['is_off']);

        $schedule = UserWeeklyMealSchedule::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'day_of_week' => $data['day_of_week'],
                'meal_time' => $data['meal_time'],
            ],
            [
                'meal_package_id' => $data['meal_package_id'],
                'is_off' => $data['is_off'],
            ]
        );

        return response()->json([
            'message' => 'Weekly meal schedule saved successfully.',
            'weekly_schedule' => $schedule->load('mealPackage'),
        ], 201);
    }

    public function update(UpdateUserWeeklyMealScheduleRequest $request, UserWeeklyMealSchedule $weeklySchedule): JsonResponse
    {
        $this->ensureOwnership($request, $weeklySchedule);

        $data = $this->prepareScheduleData(array_merge($weeklySchedule->only(['day_of_week', 'meal_time']), $request->validated()));
        $this->ensureAllowedWeeklySelection($data['day_of_week'], $data['meal_time'], $data['meal_package_id'], $data['is_off']);

        $weeklySchedule->update([
            'day_of_week' => $data['day_of_week'],
            'meal_time' => $data['meal_time'],
            'meal_package_id' => $data['meal_package_id'],
            'is_off' => $data['is_off'],
        ]);

        return response()->json([
            'message' => 'Weekly meal schedule updated successfully.',
            'weekly_schedule' => $weeklySchedule->fresh()->load('mealPackage'),
        ]);
    }

    private function prepareScheduleData(array $data): array
    {
        $isOff = (bool) ($data['is_off'] ?? false);

        return [
            'day_of_week' => $data['day_of_week'],
            'meal_time' => $data['meal_time'],
            'meal_package_id' => $isOff ? null : ($data['meal_package_id'] ?? null),
            'is_off' => $isOff,
        ];
    }

    private function ensureAllowedWeeklySelection(string $dayOfWeek, string $mealTime, ?int $mealPackageId, bool $isOff): void
    {
        if ($isOff) {
            return;
        }

        abort_if(! $mealPackageId, 422, 'Meal package is required when meal is on.');

        $package = MealPackage::query()->findOrFail($mealPackageId);
        abort_if($package->status !== 'active', 422, 'Inactive package cannot be selected.');

        $available = WeeklyMenuItem::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('meal_time', $mealTime)
            ->where('meal_package_id', $mealPackageId)
            ->where('status', 'active')
            ->exists();

        abort_if(! $available, 422, 'Selected package is not available for this day and meal time.');
    }

    private function ensureOwnership(Request $request, UserWeeklyMealSchedule $weeklySchedule): void
    {
        abort_unless($weeklySchedule->user_id === $request->user()->id, 403, 'You do not have permission to access this weekly schedule.');
    }
}
