<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:assigned,picked,delivered,failed'],
            'deliveryman_id' => ['nullable', 'integer', 'exists:users,id'],
            'schedule_date' => ['nullable', 'date'],
        ];
    }
}
