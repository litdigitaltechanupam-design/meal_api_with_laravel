<?php

namespace App\Http\Requests\Auth;

class UpdateUserMealCalendarOverrideRequest extends StoreUserMealCalendarOverrideRequest
{
    public function rules(): array
    {
        return [
            'schedule_date' => ['sometimes', 'required', 'date'],
            'meal_time' => ['sometimes', 'required', 'in:lunch,dinner'],
            'meal_package_id' => ['nullable', 'integer', 'exists:meal_packages,id'],
            'is_off' => ['nullable', 'boolean'],
        ];
    }
}
