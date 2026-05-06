<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWeeklyMenuRequest;
use App\Http\Requests\Admin\UpdateWeeklyMenuRequest;
use App\Models\MealPackage;
use App\Models\WeeklyMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyMenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'day_of_week' => ['nullable', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $weeklyMenus = WeeklyMenu::query()
            ->with('items.mealPackage')
            ->when(! empty($filters['day_of_week']), fn ($query) => $query->where('day_of_week', $filters['day_of_week']))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->where('meal_time', $filters['meal_time']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByRaw("FIELD(day_of_week, 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')")
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')")
            ->get();

        return response()->json([
            'filters' => $filters,
            'weekly_menus' => $weeklyMenus,
        ]);
    }

    public function store(StoreWeeklyMenuRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->ensurePackagesAreActive($data['items']);

        $weeklyMenu = DB::transaction(function () use ($request, $data) {
            $weeklyMenu = WeeklyMenu::updateOrCreate(
                [
                    'day_of_week' => $data['day_of_week'],
                    'meal_time' => $data['meal_time'],
                ],
                [
                    'status' => $data['status'] ?? 'active',
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]
            );

            $weeklyMenu->items()->delete();
            $weeklyMenu->items()->createMany(collect($data['items'])->map(fn ($item) => [
                'meal_package_id' => $item['meal_package_id'],
            ])->all());

            return $weeklyMenu->load('items.mealPackage');
        });

        return response()->json([
            'message' => 'Weekly menu saved successfully.',
            'weekly_menu' => $weeklyMenu,
        ], 201);
    }

    public function show(WeeklyMenu $weeklyMenu): JsonResponse
    {
        return response()->json([
            'weekly_menu' => $weeklyMenu->load('items.mealPackage'),
        ]);
    }

    public function update(UpdateWeeklyMenuRequest $request, WeeklyMenu $weeklyMenu): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('items', $data)) {
            $this->ensurePackagesAreActive($data['items']);
        }

        DB::transaction(function () use ($request, $data, $weeklyMenu) {
            $weeklyMenu->fill([
                'day_of_week' => $data['day_of_week'] ?? $weeklyMenu->day_of_week,
                'meal_time' => $data['meal_time'] ?? $weeklyMenu->meal_time,
                'status' => $data['status'] ?? $weeklyMenu->status,
                'updated_by' => $request->user()->id,
            ]);
            $weeklyMenu->save();

            if (array_key_exists('items', $data)) {
                $weeklyMenu->items()->delete();
                $weeklyMenu->items()->createMany(collect($data['items'])->map(fn ($item) => [
                    'meal_package_id' => $item['meal_package_id'],
                ])->all());
            }
        });

        return response()->json([
            'message' => 'Weekly menu updated successfully.',
            'weekly_menu' => $weeklyMenu->fresh()->load('items.mealPackage'),
        ]);
    }

    public function updateStatus(Request $request, WeeklyMenu $weeklyMenu): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $weeklyMenu->update([
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Weekly menu status updated successfully.',
            'weekly_menu' => $weeklyMenu->fresh()->load('items.mealPackage'),
        ]);
    }

    private function ensurePackagesAreActive(array $items): void
    {
        foreach ($items as $item) {
            $package = MealPackage::query()->findOrFail($item['meal_package_id']);
            abort_if($package->status !== 'active', 422, 'Inactive package cannot be assigned to weekly menu.');
        }
    }
}
