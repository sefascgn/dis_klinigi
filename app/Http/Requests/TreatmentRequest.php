<?php

namespace App\Http\Requests;

use App\Enums\TreatmentStatus;
use App\Http\Requests\Concerns\AuthorizesResource;
use App\Models\Treatment;
use App\Support\Teeth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TreatmentRequest extends FormRequest
{
    use AuthorizesResource;

    protected string $resourceModel = Treatment::class;

    protected string $routeParameter = 'treatment';

    /**
     * Doktor rolündeki kullanıcı yalnızca kendi adına tedavi kaydedebilir.
     */
    protected function prepareForValidation(): void
    {
        if ($this->user()->isDoctor()) {
            $this->merge(['doctor_id' => $this->user()->doctor?->id]);
        }
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'doctor_id' => ['required', Rule::exists('doctors', 'id')->whereNull('deleted_at')],
            'treatment_type_id' => ['required', Rule::exists('treatment_types', 'id')],
            'appointment_id' => [
                'nullable',
                Rule::exists('appointments', 'id')->where('patient_id', $this->input('patient_id')),
            ],
            'tooth_number' => ['nullable', 'integer', Rule::in(Teeth::all())],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', Rule::enum(TreatmentStatus::class)],
            'treatment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
