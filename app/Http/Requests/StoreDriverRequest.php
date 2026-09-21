<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $driverId = $this->route('driver')?->id;

        return [
            'user_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::unique('drivers', 'user_id')->ignore($driverId)->whereNull('deleted_at'),
            ],
            'license_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('drivers', 'license_number')->ignore($driverId)->whereNull('deleted_at'),
            ],
            'license_expiry' => ['nullable', 'date', 'after:today'],
            'is_active'      => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique'        => 'This user already has an active driver profile.',
            'license_number.unique' => 'This license number is already registered.',
            'license_expiry.after'  => 'The license expiry date must be in the future.',
        ];
    }
}
