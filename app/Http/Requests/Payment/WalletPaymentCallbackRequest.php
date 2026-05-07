<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletPaymentCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_payment_request_id' => ['required', 'integer', 'exists:wallet_payment_requests,id'],
            'gateway_transaction_id' => ['required', 'string', 'max:100'],
            'gateway_name' => ['required', Rule::in(['bkash', 'nagad'])],
            'status' => ['required', Rule::in(['paid', 'failed', 'cancelled'])],
            'note' => ['nullable', 'string'],
        ];
    }
}
