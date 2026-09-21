<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isDriver() || $this->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'start_km'         => ['required', 'integer', 'min:0'],
            'end_km'           => ['required', 'integer', 'gt:start_km'],
            'fuel_receipt_url' => ['nullable', 'url', 'max:2048'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_km.required' => 'Start KM is required.',
            'end_km.required'   => 'End KM is required.',
            'end_km.gt'         => 'End KM must be greater than Start KM.',
        ];
    }
}
