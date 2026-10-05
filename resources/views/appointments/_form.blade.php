@csrf

<div class="space-y-5">
    <x-patient-picker :patient="$patient" />

    <x-form.select name="doctor_id" label="Doktor" required placeholder="Doktor seçin"
                   :options="$doctors->mapWithKeys(fn ($d) => [$d->id => $d->full_name.($d->specialty ? ' — '.$d->specialty : '')])->all()"
                   :value="$appointment->doctor_id" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="starts_at" label="Tarih ve saat" type="datetime-local" step="300" required
                      :value="$appointment->starts_at?->format('Y-m-d\TH:i')" />
        <x-form.select name="duration" label="Süre" required
                       :options="collect($durations)->mapWithKeys(fn ($m) => [$m => $m.' dakika'])->all()"
                       :value="$duration" />
    </div>

    @if ($appointment->exists)
        <x-form.select name="status" label="Durum" required :options="App\Enums\AppointmentStatus::options()" :value="$appointment->status" />
    @endif

    <x-form.textarea name="notes" label="Randevu notu" :value="$appointment->notes" rows="3" placeholder="Şikayet, yapılacak işlem vb." />

    <x-form.actions :cancel="route('appointments.index', ['date' => $appointment->starts_at?->toDateString()])" />
</div>
