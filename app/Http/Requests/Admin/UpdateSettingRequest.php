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
            'app_timezone' => ['required', 'string', 'max:100'],
            'delivery_charge_enabled' => ['required', 'boolean'],
            'delivery_charge_amount' => ['required', 'numeric', 'min:0'],
            'lunch_cutoff_time' => ['required', 'date_format:H:i'],
            'dinner_cutoff_time' => ['required', 'date_format:H:i'],
        ];
    }
}
