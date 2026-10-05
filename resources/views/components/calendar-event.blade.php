{{-- Takvimde tek randevu kartı. Gün görünümünde konum/yükseklik dışarıdan "style" ile verilir. --}}
@props(['appointment', 'showDoctor' => true])

@php
    use App\Enums\AppointmentStatus;

    $classes = match ($appointment->status) {
        AppointmentStatus::Pending => 'border-amber-400 bg-amber-50 text-amber-900',
        AppointmentStatus::Confirmed => 'border-sky-500 bg-sky-50 text-sky-900',
        AppointmentStatus::Completed => 'border-emerald-500 bg-emerald-50 text-emerald-900',
        AppointmentStatus::Cancelled => 'border-gray-300 bg-gray-50 text-gray-400 line-through',
    };
@endphp

<div {{ $attributes->merge(['class' => "group overflow-hidden rounded-md border-l-4 px-2 py-1 text-xs shadow-sm {$classes}"]) }}
     title="{{ $appointment->starts_at->format('H:i') }}–{{ $appointment->ends_at->format('H:i') }} · {{ $appointment->patient->full_name }} · {{ $appointment->doctor->full_name }} · {{ $appointment->status->label() }}{{ $appointment->notes ? ' · '.$appointment->notes : '' }}">
    <div class="flex items-start justify-between gap-1">
        <div class="min-w-0">
            <p class="font-semibold">{{ $appointment->starts_at->format('H:i') }}–{{ $appointment->ends_at->format('H:i') }}</p>
            <a href="{{ route('patients.show', $appointment->patient_id) }}" class="block truncate font-medium hover:underline">{{ $appointment->patient->full_name }}</a>
            @if ($showDoctor)
                <p class="truncate opacity-75">{{ $appointment->doctor->full_name }}</p>
            @endif
        </div>
        <x-appointment-actions :appointment="$appointment" compact />
    </div>
</div>
