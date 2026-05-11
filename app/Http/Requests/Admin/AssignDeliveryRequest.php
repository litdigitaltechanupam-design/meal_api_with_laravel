<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deliveryman_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string'],
        ];
    }
}
