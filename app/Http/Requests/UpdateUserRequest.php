<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')],
            'password'            => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()],
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
            'email.unique' => 'This email address is already registered.',
        ];
    }
}
