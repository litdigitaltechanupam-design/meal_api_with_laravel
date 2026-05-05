<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'address_line' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
