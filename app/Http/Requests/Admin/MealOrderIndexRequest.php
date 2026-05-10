<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MealOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_date' => ['nullable', 'date'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
            'status' => ['nullable', 'in:confirmed,prepared,out_for_delivery,delivered,failed,cancelled'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
