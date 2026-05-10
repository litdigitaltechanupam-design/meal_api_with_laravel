<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subarea = $this->route('subarea');
        $areaId = $this->integer('area_id') ?: $subarea?->area_id;

        return [
            'area_id' => ['sometimes', 'required', 'integer', 'exists:areas,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('subareas', 'name')
                    ->ignore($subarea?->id)
                    ->where(fn ($query) => $query->where('area_id', $areaId)),
            ],
            'status' => ['sometimes', 'required', 'in:active,inactive'],
        ];
    }
}
