<?php

namespace App\Http\Requests\Auth;

class UpdateUserWeeklyMealScheduleRequest extends StoreUserWeeklyMealScheduleRequest
{
    public function rules(): array
    {
        return [
            'day_of_week' => ['sometimes', 'required', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'meal_time' => ['sometimes', 'required', 'in:lunch,dinner'],
            'meal_package_id' => ['nullable', 'integer', 'exists:meal_packages,id'],
            'is_off' => ['nullable', 'boolean'],
        ];
    }
}
