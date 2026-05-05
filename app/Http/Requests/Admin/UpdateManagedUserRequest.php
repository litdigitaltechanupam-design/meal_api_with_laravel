<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $managedUserId = $this->route('user')->id;
        $isAdmin = $this->user()->role === 'admin';

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($managedUserId)],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($managedUserId)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'role' => $isAdmin ? ['nullable', Rule::in(['admin', 'manager', 'deliveryman', 'customer'])] : ['prohibited'],
        ];
    }
}
