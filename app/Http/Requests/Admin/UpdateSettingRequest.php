<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_charge_enabled' => ['required', 'boolean'],
            'delivery_charge_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
