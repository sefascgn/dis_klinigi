<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use App\Http\Requests\Concerns\AuthorizesResource;
use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AppointmentRequest extends FormRequest
{
    use AuthorizesResource;

    public const DURATIONS = [15, 20, 30, 45, 60, 90, 120, 180];

    protected string $resourceModel = Appointment::class;

    protected string $routeParameter = 'appointment';

    public function rules(): array
    {
        $isCreate = $this->route('appointment') === null;

        return [
            'patient_id' => ['required', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'doctor_id' => [
                'required',
                Rule::exists('doctors', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'starts_at' => array_filter(['required', 'date', $isCreate ? 'after:now' : null]),
            'duration' => ['required', 'integer', Rule::in(self::DURATIONS)],
            'status' => [$isCreate ? 'nullable' : 'required', Rule::enum(AppointmentStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Doktorun aynı saatte başka aktif randevusu olmamalı.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || $this->status() === AppointmentStatus::Cancelled) {
                    return;
                }

                [$start, $end] = [$this->startsAt(), $this->endsAt()];

                $conflict = Appointment::query()
                    ->overlapping((int) $this->input('doctor_id'), $start, $end, $this->route('appointment')?->id)
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('starts_at', 'Doktorun bu saat aralığında başka bir randevusu var.');
                }
            },
        ];
    }

    /**
     * Veritabanına yazılacak alanlar (süre, bitiş zamanına dönüştürülür).
     */
    public function appointmentData(): array
    {
        return [
            'patient_id' => $this->validated('patient_id'),
            'doctor_id' => $this->validated('doctor_id'),
            'starts_at' => $this->startsAt(),
            'ends_at' => $this->endsAt(),
            'status' => $this->status(),
            'notes' => $this->validated('notes'),
        ];
    }

    private function startsAt(): Carbon
    {
        return Carbon::parse($this->input('starts_at'))->seconds(0);
    }

    private function endsAt(): Carbon
    {
        return $this->startsAt()->addMinutes((int) $this->input('duration'));
    }

    private function status(): AppointmentStatus
    {
        return AppointmentStatus::tryFrom((string) $this->input('status')) ?? AppointmentStatus::Pending;
    }
}
