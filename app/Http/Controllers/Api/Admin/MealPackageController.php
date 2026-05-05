<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MealPackageIndexRequest;
use App\Http\Requests\Admin\StoreMealPackageRequest;
use App\Http\Requests\Admin\UpdateMealPackageRequest;
use App\Models\MealPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MealPackageController extends Controller
{
    public function index(MealPackageIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $packages = MealPackage::query()
            ->when(! empty($filters['name']), fn ($query) => $query->where('name', 'like', '%'.$filters['name'].'%'))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['price_from']), fn ($query) => $query->where('price', '>=', $filters['price_from']))
            ->when(isset($filters['price_to']), fn ($query) => $query->where('price', '<=', $filters['price_to']))
            ->latest()
            ->get();

        return response()->json([
            'filters' => $filters,
            'meal_packages' => $packages,
        ]);
    }

    public function store(StoreMealPackageRequest $request): JsonResponse
    {
        $data = $request->validated();

        $package = MealPackage::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'],
            'price' => $data['price'],
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Meal package created successfully.',
            'meal_package' => $package,
        ], 201);
    }

    public function show(MealPackage $mealPackage): JsonResponse
    {
        return response()->json([
            'meal_package' => $mealPackage,
        ]);
    }

    public function update(UpdateMealPackageRequest $request, MealPackage $mealPackage): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $mealPackage->name = $data['name'];
            $mealPackage->slug = Str::slug($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $mealPackage->description = $data['description'];
        }

        if (array_key_exists('price', $data)) {
            $mealPackage->price = $data['price'];
        }

        if (array_key_exists('status', $data)) {
            $mealPackage->status = $data['status'];
        }

        $mealPackage->updated_by = $request->user()->id;
        $mealPackage->save();

        return response()->json([
            'message' => 'Meal package updated successfully.',
            'meal_package' => $mealPackage->fresh(),
        ]);
    }

    public function updateStatus(Request $request, MealPackage $mealPackage): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $mealPackage->update([
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Meal package status updated successfully.',
            'meal_package' => $mealPackage->fresh(),
        ]);
    }
}
