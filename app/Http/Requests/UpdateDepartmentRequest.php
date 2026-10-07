<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->slug === 'super_admin';
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('departments', 'name')
                    ->ignore($departmentId)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable', 'string', 'max:20', 'alpha_num',
                Rule::unique('departments', 'code')
                    ->ignore($departmentId)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A department with this name already exists.',
            'code.unique' => 'This department code is already in use.',
            'code.alpha_num' => 'The code must contain only letters and numbers (e.g. IT, HR, FIN).',
        ];
    }
}
