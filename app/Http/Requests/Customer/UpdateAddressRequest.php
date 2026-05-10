<?php

namespace App\Http\Requests\Customer;

class UpdateAddressRequest extends StoreAddressRequest
{
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'max:50'],
            'address_line' => ['sometimes', 'required', 'string', 'max:255'],
            'area_id' => ['sometimes', 'required', 'integer', 'exists:areas,id'],
            'subarea_id' => ['sometimes', 'required', 'integer', 'exists:subareas,id'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
