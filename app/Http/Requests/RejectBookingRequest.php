<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isPic() || $this->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'A rejection reason is required.',
            'rejection_reason.min'      => 'Rejection reason must be at least 5 characters.',
        ];
    }
}
