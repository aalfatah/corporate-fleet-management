<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isPic() || $this->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return [];
    }
}
