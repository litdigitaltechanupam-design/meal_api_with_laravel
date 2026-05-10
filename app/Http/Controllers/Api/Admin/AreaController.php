<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAreaRequest;
use App\Http\Requests\Admin\UpdateAreaRequest;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $areas = Area::query()
            ->when(! empty($filters['name']), fn ($query) => $query->where('name', 'like', '%'.$filters['name'].'%'))
            ->when(! empty($filters['city']), fn ($query) => $query->where('city', 'like', '%'.$filters['city'].'%'))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->get();

        return response()->json(['areas' => $areas]);
    }

    public function store(StoreAreaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $area = Area::create($data);

        return response()->json([
            'message' => 'Area created successfully.',
            'area' => $area,
        ], 201);
    }

    public function show(Area $area): JsonResponse
    {
        return response()->json(['area' => $area]);
    }

    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        $area->update($data);

        return response()->json([
            'message' => 'Area updated successfully.',
            'area' => $area->fresh(),
        ]);
    }

    public function updateStatus(Request $request, Area $area): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $area->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'Area status updated successfully.',
            'area' => $area->fresh(),
        ]);
    }
}
