<?php

namespace App\Http\Requests\Deliveryman;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:assigned,picked,delivered,failed'],
            'schedule_date' => ['nullable', 'date'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }
}
