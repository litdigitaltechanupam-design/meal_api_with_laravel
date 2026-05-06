<?php

namespace Database\Seeders;

use App\Models\MealPackage;
use App\Models\User;
use App\Models\UserWeeklySchedule;
use Illuminate\Database\Seeder;

class UserWeeklyScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::query()->where('role', 'customer')->first();

        if (! $customer) {
            return;
        }

        $packages = MealPackage::query()
            ->pluck('id', 'name');

        $weeklySchedules = [
            [
                'day_of_week' => 'saturday',
                'meal_time' => 'lunch',
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 1'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'saturday',
                'meal_time' => 'dinner',
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 2'], 'quantity' => 1],
                    ['meal_package_id' => $packages['Package 4'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'sunday',
                'meal_time' => 'lunch',
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 5'], 'quantity' => 2],
                ],
            ],
            [
                'day_of_week' => 'sunday',
                'meal_time' => 'dinner',
                'is_off' => true,
                'items' => [],
            ],
            [
                'day_of_week' => 'monday',
                'meal_time' => 'lunch',
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 1'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'monday',
                'meal_time' => 'dinner',
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 2'], 'quantity' => 2],
                ],
            ],
            [
                'day_of_week' => 'tuesday',
                'meal_time' => 'lunch',
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 3'], 'quantity' => 1],
                    ['meal_package_id' => $packages['Package 5'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'tuesday',
                'meal_time' => 'dinner',
                'is_off' => true,
                'items' => [],
            ],
        ];

        foreach ($weeklySchedules as $entry) {
            $schedule = UserWeeklySchedule::query()->create([
                'user_id' => $customer->id,
                'day_of_week' => $entry['day_of_week'],
                'meal_time' => $entry['meal_time'],
                'is_off' => $entry['is_off'],
            ]);

            if (! $entry['is_off']) {
                $schedule->items()->createMany($entry['items']);
            }
        }
    }
}
