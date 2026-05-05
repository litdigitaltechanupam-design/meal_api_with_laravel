<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWeeklyMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => ['sometimes', 'required', Rule::in(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])],
            'meal_time' => ['sometimes', 'required', Rule::in(['lunch', 'dinner'])],
            'meal_package_id' => ['sometimes', 'required', 'integer', 'exists:meal_packages,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
