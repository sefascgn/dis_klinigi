@csrf

@php
    $prices = $treatmentTypes->mapWithKeys(fn ($t) => [$t->id => $t->default_price])->all();
    // Kayıtlı tedavinin ücretini doktor değiştiremez (bkz. TreatmentRequest::after).
    $priceLocked = $treatment->exists && auth()->user()->isDoctor();
@endphp

{{-- İşlem seçilince fiyat boşsa veya önceki varsayılan fiyattaysa varsayılan ücret otomatik gelir. --}}
<div class="space-y-5" x-data="{
        prices: @js($prices),
        price: @js((string) old('price', $treatment->price ?? '')),
        locked: @js($priceLocked),
        pick(id) {
            const current = Object.values(this.prices).map(String);
            if (! this.locked && (this.price === '' || current.includes(String(this.price)))) {
                this.price = this.prices[id] ?? '';
            }
        },
     }">
    @if ($treatment->appointment_id)
        <input type="hidden" name="appointment_id" value="{{ $treatment->appointment_id }}">
    @endif

    <x-patient-picker :patient="$patient" :locked="(bool) $treatment->appointment_id" />

    @if (auth()->user()->isDoctor())
        <div>
            <span class="form-label">Doktor</span>
            <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">{{ auth()->user()->doctor?->full_name ?? 'Hesabınıza bağlı doktor kaydı yok' }}</p>
            @error('doctor_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    @else
        <x-form.select name="doctor_id" label="Doktor" required placeholder="Doktor seçin"
                       :options="$doctors->mapWithKeys(fn ($d) => [$d->id => $d->full_name])->all()"
                       :value="$treatment->doctor_id" />
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.select name="treatment_type_id" label="İşlem" required placeholder="İşlem seçin"
                       :options="$treatmentTypes->mapWithKeys(fn ($t) => [$t->id => $t->name])->all()"
                       :value="$treatment->treatment_type_id" x-on:change="pick($event.target.value)" />
        <x-form.select name="tooth_number" label="Diş numarası (FDI)" :options="App\Support\Teeth::groupedOptions()"
                       :value="$treatment->tooth_number" placeholder="Genel işlem (diş seçilmedi)" />

        <div>
            <label for="price" class="form-label">Ücret (₺) <span class="text-red-500">*</span></label>
            <input type="number" id="price" name="price" x-model="price" step="0.01" min="0" required @readonly($priceLocked)
                   @class(['form-input', 'border-red-400' => $errors->has('price'), 'bg-gray-50 text-gray-500' => $priceLocked])>
            @if ($priceLocked)<p class="mt-1 text-xs text-gray-500">Ücreti yalnızca yönetici değiştirebilir.</p>@endif
            @error('price')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <x-form.input name="treatment_date" label="Tarih" type="date" required :value="$treatment->treatment_date?->toDateString()" />

        <x-form.select name="status" label="Durum" required :options="App\Enums\TreatmentStatus::options()" :value="$treatment->status" class="sm:col-span-2" />
    </div>

    <x-form.textarea name="notes" label="Notlar" :value="$treatment->notes" rows="3" />

    <x-form.actions :cancel="$patient ? route('patients.show', $patient) : route('treatments.index')" />
</div>
