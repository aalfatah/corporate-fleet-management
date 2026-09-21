<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate_number')
                    ->ignore($vehicleId)
                    ->whereNull('deleted_at'),
            ],
            'brand'          => ['required', 'string', 'max:100'],
            'model'          => ['required', 'string', 'max:100'],
            'color'          => ['nullable', 'string', 'max:50'],
            'year'           => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'capacity'       => ['required', 'integer', 'min:1', 'max:60'],
            'fuel_type'      => ['nullable', Rule::in(['petrol', 'diesel', 'electric', 'hybrid'])],
            'current_km'     => ['nullable', 'integer', 'min:0'],
            'current_status' => [
                'nullable',
                Rule::in([
                    Vehicle::STATUS_AVAILABLE,
                    Vehicle::STATUS_IN_USE,
                    Vehicle::STATUS_MAINTENANCE,
                ]),
            ],
            'photo'          => ['nullable', 'image', 'max:2048'],
        ];
    }
}
