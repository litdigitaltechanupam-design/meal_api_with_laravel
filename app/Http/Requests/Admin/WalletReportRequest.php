<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WalletReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'type' => ['nullable', 'in:top_up,meal_charge,refund,adjustment,bonus'],
            'direction' => ['nullable', 'in:credit,debit'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
