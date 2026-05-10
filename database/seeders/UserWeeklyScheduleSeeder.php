<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\MealPackage;
use App\Models\Subarea;
use App\Models\User;
use App\Models\UserAddress;
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

        $packages = MealPackage::query()->pluck('id', 'name');
        $homeArea = Area::query()->where('name', 'Modina Market')->first();
        $officeArea = Area::query()->where('name', 'Korer Para')->first();
        $homeSubarea = Subarea::query()->where('name', 'Modina Market North')->first();
        $officeSubarea = Subarea::query()->where('name', 'Korer Para Office Zone')->first();

        if (! $homeArea || ! $officeArea || ! $homeSubarea || ! $officeSubarea) {
            return;
        }

        $homeAddress = UserAddress::query()->updateOrCreate(
            [
                'user_id' => $customer->id,
                'label' => 'Home',
            ],
            [
                'address_line' => 'Modina Market, House 12',
                'area_id' => $homeArea->id,
                'subarea_id' => $homeSubarea->id,
                'city' => 'Sylhet',
                'notes' => 'Home delivery address',
                'is_default' => true,
            ]
        );

        $officeAddress = UserAddress::query()->updateOrCreate(
            [
                'user_id' => $customer->id,
                'label' => 'Office',
            ],
            [
                'address_line' => 'Korer Para, Office Tower 3',
                'area_id' => $officeArea->id,
                'subarea_id' => $officeSubarea->id,
                'city' => 'Sylhet',
                'notes' => 'Office delivery address',
                'is_default' => false,
            ]
        );

        $weeklySchedules = [
            [
                'day_of_week' => 'saturday',
                'meal_time' => 'lunch',
                'address_id' => $officeAddress->id,
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 1'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'saturday',
                'meal_time' => 'dinner',
                'address_id' => $homeAddress->id,
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 2'], 'quantity' => 1],
                    ['meal_package_id' => $packages['Package 4'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'sunday',
                'meal_time' => 'lunch',
                'address_id' => $officeAddress->id,
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 5'], 'quantity' => 2],
                ],
            ],
            [
                'day_of_week' => 'sunday',
                'meal_time' => 'dinner',
                'address_id' => null,
                'is_off' => true,
                'items' => [],
            ],
            [
                'day_of_week' => 'monday',
                'meal_time' => 'lunch',
                'address_id' => $officeAddress->id,
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 1'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'monday',
                'meal_time' => 'dinner',
                'address_id' => $homeAddress->id,
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 2'], 'quantity' => 2],
                ],
            ],
            [
                'day_of_week' => 'tuesday',
                'meal_time' => 'lunch',
                'address_id' => $officeAddress->id,
                'is_off' => false,
                'items' => [
                    ['meal_package_id' => $packages['Package 3'], 'quantity' => 1],
                    ['meal_package_id' => $packages['Package 5'], 'quantity' => 1],
                ],
            ],
            [
                'day_of_week' => 'tuesday',
                'meal_time' => 'dinner',
                'address_id' => null,
                'is_off' => true,
                'items' => [],
            ],
        ];

        foreach ($weeklySchedules as $entry) {
            $schedule = UserWeeklySchedule::query()->create([
                'user_id' => $customer->id,
                'address_id' => $entry['address_id'],
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
