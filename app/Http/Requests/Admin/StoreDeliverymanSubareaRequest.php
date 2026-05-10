<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliverymanSubareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deliveryman_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'deliveryman')),
            ],
            'subarea_id' => ['required', 'integer', 'exists:subareas,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
