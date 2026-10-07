<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isEmployee() || $this->user()->isSuperAdmin() || $this->user()->isPic());
    }

    public function rules(): array
    {
        return [
            'vehicle_id'      => ['required', 'uuid', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'start_time'      => ['required', 'date', 'after:now'],
            'end_time'        => ['required', 'date', 'after:start_time'],
            'destination'     => ['required', 'string', 'max:255'],
            'purpose'         => ['required', 'string', 'max:1000'],
            'passenger_count' => ['required', 'integer', 'min:1', 'max:50'],
            'manager_id'      => ['nullable', 'uuid', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.required'  => 'Please select a vehicle.',
            'start_time.after'     => 'Start time must be in the future.',
            'end_time.after'       => 'End time must be after the start time.',
            'destination.required' => 'Please provide the trip destination.',
            'purpose.required'     => 'Please provide the trip purpose.',
        ];
    }
}
