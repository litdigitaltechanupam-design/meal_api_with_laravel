<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MealPackageListRequest;
use App\Models\MealPackage;
use Illuminate\Http\JsonResponse;

class MealPackageController extends Controller
{
    public function index(MealPackageListRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $packages = MealPackage::query()
            ->where('status', 'active')
            ->when(! empty($filters['name']), fn ($query) => $query->where('name', 'like', '%'.$filters['name'].'%'))
            ->latest()
            ->get();

        return response()->json([
            'meal_packages' => $packages,
        ]);
    }

    public function show(MealPackage $mealPackage): JsonResponse
    {
        abort_if($mealPackage->status !== 'active', 404, 'Meal package not found.');

        return response()->json([
            'meal_package' => $mealPackage,
        ]);
    }
}
