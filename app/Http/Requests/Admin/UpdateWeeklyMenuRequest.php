<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWeeklyMenuRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.meal_package_id' => ['required_with:items', 'integer', 'exists:meal_packages,id', 'distinct'],
        ];
    }
}
