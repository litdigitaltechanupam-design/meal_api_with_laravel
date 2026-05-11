<?php

namespace App\Http\Requests\Deliveryman;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', 'in:assigned,picked,delivered,failed'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
        ];
    }
}
