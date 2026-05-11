<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'meal_time' => ['nullable', 'in:lunch,dinner'],
            'status' => ['nullable', 'in:confirmed,prepared,out_for_delivery,delivered,failed,cancelled'],
        ];
    }
}
