<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password'            => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'role_id'             => ['required', 'uuid', Rule::exists('roles', 'id')],
            'department_id'       => ['nullable', 'uuid', Rule::exists('departments', 'id')],
            'manager_id'          => ['nullable', 'uuid', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'phone'               => ['nullable', 'string', 'max:30'],
            'must_change_password' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'   => 'This email address is already registered.',
            'password.min'   => 'Password must be at least 8 characters with uppercase, lowercase, and numbers.',
        ];
    }
}
