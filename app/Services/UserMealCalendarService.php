<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMealCalendarOverride;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class UserMealCalendarService
{
    public function buildMonth(User $user, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $weeklySchedules = $user->weeklyMealSchedules()
            ->with('mealPackage')
            ->get()
            ->keyBy(fn ($schedule) => $schedule->day_of_week.'_'.$schedule->meal_time);

        $overrides = $user->mealCalendarOverrides()
            ->with('mealPackage')
            ->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($override) => $override->schedule_date->toDateString().'_'.$override->meal_time);

        $days = [];
        $monthlyTotal = 0;
        $totalMealsOn = 0;
        $totalMealsOff = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dayName = strtolower($date->englishDayOfWeek);

            $lunch = $this->resolveMealSlot($weeklySchedules, $overrides, $date, $dayName, 'lunch');
            $dinner = $this->resolveMealSlot($weeklySchedules, $overrides, $date, $dayName, 'dinner');

            $dailyTotal = $lunch['price'] + $dinner['price'];
            $monthlyTotal += $dailyTotal;
            $totalMealsOn += ($lunch['is_off'] ? 0 : 1) + ($dinner['is_off'] ? 0 : 1);
            $totalMealsOff += ($lunch['is_off'] ? 1 : 0) + ($dinner['is_off'] ? 1 : 0);

            $days[] = [
                'date' => $date->toDateString(),
                'day_of_week' => $dayName,
                'lunch' => $lunch,
                'dinner' => $dinner,
                'daily_total' => round($dailyTotal, 2),
            ];
        }

        return [
            'month' => $month,
            'summary' => [
                'monthly_total' => round($monthlyTotal, 2),
                'total_meals_on' => $totalMealsOn,
                'total_meals_off' => $totalMealsOff,
            ],
            'days' => $days,
        ];
    }

    private function resolveMealSlot($weeklySchedules, $overrides, Carbon $date, string $dayName, string $mealTime): array
    {
        $overrideKey = $date->toDateString().'_'.$mealTime;
        $weeklyKey = $dayName.'_'.$mealTime;

        $source = $overrides->get($overrideKey) ?: $weeklySchedules->get($weeklyKey);
        $hasSource = $source !== null;
        $isOff = ! $hasSource || (bool) $source->is_off;
        $package = $hasSource ? $source->mealPackage : null;
        $price = $isOff || ! $package ? 0 : (float) $package->price;

        return [
            'meal_time' => $mealTime,
            'is_off' => $isOff,
            'package_id' => $package?->id,
            'package_name' => $package?->name,
            'description' => $package?->description,
            'price' => round($price, 2),
            'source' => $overrides->has($overrideKey) ? 'override' : 'weekly_default',
        ];
    }
}
