<?php

namespace App\Http\Requests\Customer;

class UpdateCalendarOverrideRequest extends StoreCalendarOverrideRequest
{
    public function rules(): array
    {
        return [
            'schedule_date' => ['sometimes', 'required', 'date'],
            'meal_time' => ['sometimes', 'required', 'in:lunch,dinner'],
            'is_off' => ['nullable', 'boolean'],
            'items' => ['sometimes', 'array'],
            'items.*.meal_package_id' => ['required_with:items', 'integer', 'exists:meal_packages,id', 'distinct'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
