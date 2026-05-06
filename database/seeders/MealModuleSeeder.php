<?php

namespace Database\Seeders;

use App\Models\MealPackage;
use App\Models\User;
use App\Models\WeeklyMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MealModuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', 'admin')->first();
        $adminId = $admin?->id;

        $packages = [
            ['name' => 'Package 1', 'description' => 'dal, mas, vat', 'price' => 80],
            ['name' => 'Package 2', 'description' => 'dal, mangsho, vat', 'price' => 120],
            ['name' => 'Package 3', 'description' => 'dal, vagetable, vat', 'price' => 80],
            ['name' => 'Package 4', 'description' => 'dal, dim, vat', 'price' => 70],
            ['name' => 'Package 5', 'description' => 'dal, shidol, vat', 'price' => 80],
            ['name' => 'Package 6', 'description' => 'dal, bor mas, vat', 'price' => 150],
            ['name' => 'Package 7', 'description' => 'dal, rost, vat', 'price' => 80],
            ['name' => 'Package 8', 'description' => 'dal, sak, vat', 'price' => 110],
        ];

        $packageMap = [];

        foreach ($packages as $package) {
            $mealPackage = MealPackage::query()->create([
                'name' => $package['name'],
                'slug' => Str::slug($package['name']),
                'description' => $package['description'],
                'price' => $package['price'],
                'status' => 'active',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            $packageMap[$package['name']] = $mealPackage->id;
        }

        $weeklyMenus = [
            ['day_of_week' => 'saturday', 'meal_time' => 'lunch', 'items' => ['Package 1', 'Package 3']],
            ['day_of_week' => 'saturday', 'meal_time' => 'dinner', 'items' => ['Package 2', 'Package 4']],
            ['day_of_week' => 'sunday', 'meal_time' => 'lunch', 'items' => ['Package 5', 'Package 8']],
            ['day_of_week' => 'sunday', 'meal_time' => 'dinner', 'items' => ['Package 6', 'Package 4']],
            ['day_of_week' => 'monday', 'meal_time' => 'lunch', 'items' => ['Package 1', 'Package 7']],
            ['day_of_week' => 'monday', 'meal_time' => 'dinner', 'items' => ['Package 2', 'Package 8']],
            ['day_of_week' => 'tuesday', 'meal_time' => 'lunch', 'items' => ['Package 3', 'Package 5']],
            ['day_of_week' => 'tuesday', 'meal_time' => 'dinner', 'items' => ['Package 4', 'Package 6']],
            ['day_of_week' => 'wednesday', 'meal_time' => 'lunch', 'items' => ['Package 1', 'Package 8']],
            ['day_of_week' => 'wednesday', 'meal_time' => 'dinner', 'items' => ['Package 2', 'Package 7']],
            ['day_of_week' => 'thursday', 'meal_time' => 'lunch', 'items' => ['Package 3', 'Package 4']],
            ['day_of_week' => 'thursday', 'meal_time' => 'dinner', 'items' => ['Package 5', 'Package 6']],
            ['day_of_week' => 'friday', 'meal_time' => 'lunch', 'items' => ['Package 1', 'Package 5']],
            ['day_of_week' => 'friday', 'meal_time' => 'dinner', 'items' => ['Package 2', 'Package 4']],
        ];

        foreach ($weeklyMenus as $menu) {
            $weeklyMenu = WeeklyMenu::query()->create([
                'day_of_week' => $menu['day_of_week'],
                'meal_time' => $menu['meal_time'],
                'status' => 'active',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            $weeklyMenu->items()->createMany(
                collect($menu['items'])->map(fn ($packageName) => [
                    'meal_package_id' => $packageMap[$packageName],
                ])->all()
            );
        }
    }
}
