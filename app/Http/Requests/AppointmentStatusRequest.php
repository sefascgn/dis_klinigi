<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('appointment'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'cancellation_reason' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($this->input('status') === AppointmentStatus::Cancelled->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Randevu iptal edilirken iptal nedeni girilmelidir.',
        ];
    }
}
