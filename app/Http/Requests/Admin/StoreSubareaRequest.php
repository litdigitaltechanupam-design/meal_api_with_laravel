<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('subareas', 'name')->where(fn ($query) => $query->where('area_id', $this->integer('area_id'))),
            ],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
