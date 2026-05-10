<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubareaRequest;
use App\Http\Requests\Admin\UpdateSubareaRequest;
use App\Models\Subarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubareaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'name' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $subareas = Subarea::query()
            ->with('area')
            ->when(! empty($filters['area_id']), fn ($query) => $query->where('area_id', $filters['area_id']))
            ->when(! empty($filters['name']), fn ($query) => $query->where('name', 'like', '%'.$filters['name'].'%'))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->get();

        return response()->json(['subareas' => $subareas]);
    }

    public function store(StoreSubareaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name'].'-'.$data['area_id']);

        $subarea = Subarea::create($data);

        return response()->json([
            'message' => 'Subarea created successfully.',
            'subarea' => $subarea->load('area'),
        ], 201);
    }

    public function show(Subarea $subarea): JsonResponse
    {
        return response()->json(['subarea' => $subarea->load('area')]);
    }

    public function update(UpdateSubareaRequest $request, Subarea $subarea): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data) || array_key_exists('area_id', $data)) {
            $name = $data['name'] ?? $subarea->name;
            $areaId = $data['area_id'] ?? $subarea->area_id;
            $data['slug'] = Str::slug($name.'-'.$areaId);
        }

        $subarea->update($data);

        return response()->json([
            'message' => 'Subarea updated successfully.',
            'subarea' => $subarea->fresh()->load('area'),
        ]);
    }

    public function updateStatus(Request $request, Subarea $subarea): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $subarea->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'Subarea status updated successfully.',
            'subarea' => $subarea->fresh()->load('area'),
        ]);
    }
}
