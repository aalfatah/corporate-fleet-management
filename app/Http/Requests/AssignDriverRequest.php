<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'driver_id' => [
                'required',
                'uuid',
                Rule::exists('drivers', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'vehicle_id' => [
                'nullable',
                'uuid',
                Rule::exists('vehicles', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.required' => 'Please select a driver to assign.',
            'driver_id.exists'   => 'Selected driver is either inactive or does not exist.',
            'vehicle_id.exists'  => 'Selected vehicle does not exist.',
        ];
    }
}
