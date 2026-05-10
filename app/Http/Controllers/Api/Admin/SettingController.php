<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'settings' => [
                'delivery_charge_enabled' => filter_var(Setting::query()->where('key', 'delivery_charge_enabled')->value('value') ?? '1', FILTER_VALIDATE_BOOL),
                'delivery_charge_amount' => (float) (Setting::query()->where('key', 'delivery_charge_amount')->value('value') ?? 0),
            ],
        ]);
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value]
            );
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => [
                'delivery_charge_enabled' => filter_var(Setting::query()->where('key', 'delivery_charge_enabled')->value('value') ?? '1', FILTER_VALIDATE_BOOL),
                'delivery_charge_amount' => (float) (Setting::query()->where('key', 'delivery_charge_amount')->value('value') ?? 0),
            ],
        ]);
    }
}
