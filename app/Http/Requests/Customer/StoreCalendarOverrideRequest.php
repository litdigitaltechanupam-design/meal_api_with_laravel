<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarOverrideRequest extends FormRequest
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
            'is_off' => ['nullable', 'boolean'],
            'items' => ['required_without:is_off', 'array'],
            'items.*.meal_package_id' => ['required_with:items', 'integer', 'exists:meal_packages,id', 'distinct'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
