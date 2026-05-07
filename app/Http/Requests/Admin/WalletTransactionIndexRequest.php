<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletTransactionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['top_up', 'meal_charge', 'refund', 'adjustment', 'bonus'])],
            'direction' => ['nullable', Rule::in(['credit', 'debit'])],
            'status' => ['nullable', Rule::in(['pending', 'completed', 'failed', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
