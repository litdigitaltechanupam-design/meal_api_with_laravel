<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWeeklyMenuItemRequest;
use App\Http\Requests\Admin\UpdateWeeklyMenuItemRequest;
use App\Models\MealPackage;
use App\Models\WeeklyMenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeeklyMenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'day_of_week' => ['nullable', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $items = WeeklyMenuItem::query()
            ->with('mealPackage')
            ->when(! empty($filters['day_of_week']), fn ($query) => $query->where('day_of_week', $filters['day_of_week']))
            ->when(! empty($filters['meal_time']), fn ($query) => $query->where('meal_time', $filters['meal_time']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByRaw("FIELD(day_of_week, 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')")
            ->orderByRaw("FIELD(meal_time, 'lunch', 'dinner')")
            ->get();

        return response()->json([
            'filters' => $filters,
            'weekly_menu_items' => $items,
        ]);
    }

    public function store(StoreWeeklyMenuItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->ensurePackageIsActive((int) $data['meal_package_id']);

        $item = WeeklyMenuItem::create([
            'day_of_week' => $data['day_of_week'],
            'meal_time' => $data['meal_time'],
            'meal_package_id' => $data['meal_package_id'],
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Weekly menu item created successfully.',
            'weekly_menu_item' => $item->load('mealPackage'),
        ], 201);
    }

    public function show(WeeklyMenuItem $weeklyMenuItem): JsonResponse
    {
        return response()->json([
            'weekly_menu_item' => $weeklyMenuItem->load('mealPackage'),
        ]);
    }

    public function update(UpdateWeeklyMenuItemRequest $request, WeeklyMenuItem $weeklyMenuItem): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('meal_package_id', $data)) {
            $this->ensurePackageIsActive((int) $data['meal_package_id']);
        }

        $weeklyMenuItem->fill($data);
        $weeklyMenuItem->updated_by = $request->user()->id;
        $weeklyMenuItem->save();

        return response()->json([
            'message' => 'Weekly menu item updated successfully.',
            'weekly_menu_item' => $weeklyMenuItem->fresh()->load('mealPackage'),
        ]);
    }

    public function updateStatus(Request $request, WeeklyMenuItem $weeklyMenuItem): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $weeklyMenuItem->update([
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Weekly menu item status updated successfully.',
            'weekly_menu_item' => $weeklyMenuItem->fresh()->load('mealPackage'),
        ]);
    }

    private function ensurePackageIsActive(int $mealPackageId): void
    {
        $package = MealPackage::query()->findOrFail($mealPackageId);

        abort_if($package->status !== 'active', 422, 'Inactive package cannot be assigned to weekly menu.');
    }
}
