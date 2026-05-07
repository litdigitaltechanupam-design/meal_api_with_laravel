<?php

namespace App\Http\Requests\Customer;

class UpdateWeeklyScheduleRequest extends StoreWeeklyScheduleRequest
{
    public function rules(): array
    {
        return [
            'day_of_week' => ['sometimes', 'required', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'meal_time' => ['sometimes', 'required', 'in:lunch,dinner'],
            'is_off' => ['nullable', 'boolean'],
            'items' => ['sometimes', 'array'],
            'items.*.meal_package_id' => ['required_with:items', 'integer', 'exists:meal_packages,id', 'distinct'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
