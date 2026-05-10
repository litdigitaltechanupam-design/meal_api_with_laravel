<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliverymanAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deliveryman_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'deliveryman')),
            ],
            'area_id' => ['sometimes', 'required', 'integer', 'exists:areas,id'],
            'status' => ['sometimes', 'required', 'in:active,inactive'],
        ];
    }
}
