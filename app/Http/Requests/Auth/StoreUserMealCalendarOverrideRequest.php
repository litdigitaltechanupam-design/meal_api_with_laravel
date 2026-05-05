<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserMealCalendarOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_date' => ['required', 'date'],
            'meal_time' => ['required', Rule::in(['lunch', 'dinner'])],
            'meal_package_id' => ['nullable', 'integer', 'exists:meal_packages,id'],
            'is_off' => ['nullable', 'boolean'],
        ];
    }
}
