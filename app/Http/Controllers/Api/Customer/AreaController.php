<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Subarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $areas = Area::query()
            ->where('status', 'active')
            ->when(! empty($filters['city']), fn ($query) => $query->where('city', 'like', '%'.$filters['city'].'%'))
            ->with(['subareas' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return response()->json(['areas' => $areas]);
    }

    public function subareas(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ]);

        $subareas = Subarea::query()
            ->with('area')
            ->where('status', 'active')
            ->when(! empty($filters['area_id']), fn ($query) => $query->where('area_id', $filters['area_id']))
            ->orderBy('name')
            ->get();

        return response()->json(['subareas' => $subareas]);
    }
}
