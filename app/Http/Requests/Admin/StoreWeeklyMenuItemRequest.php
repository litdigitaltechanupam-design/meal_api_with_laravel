<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeeklyMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => ['required', Rule::in(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])],
            'meal_time' => ['required', Rule::in(['lunch', 'dinner'])],
            'meal_package_id' => ['required', 'integer', 'exists:meal_packages,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
