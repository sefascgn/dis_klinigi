<?php

namespace App\Http\Requests;

use App\Models\ClinicSetting;
use Illuminate\Foundation\Http\FormRequest;

class ClinicSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', ClinicSetting::current());
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s()-]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'digits_between:10,11'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            'default_appointment_duration' => ['required', 'integer', 'min:5', 'max:480'],
        ];
    }
}
