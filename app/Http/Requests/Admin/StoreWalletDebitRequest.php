<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalletDebitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'type' => ['required', Rule::in(['meal_charge', 'adjustment'])],
            'payment_method' => ['required', Rule::in(['cash', 'bkash', 'nagad', 'card', 'bank', 'system'])],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'integer'],
            'gateway_name' => ['nullable', 'string', 'max:50'],
            'gateway_transaction_id' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ];
    }
}
