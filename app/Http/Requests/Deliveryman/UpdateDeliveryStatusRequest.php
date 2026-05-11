<?php

namespace App\Http\Requests\Deliveryman;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:picked,delivered,failed'],
            'note' => ['nullable', 'string'],
        ];
    }
}
