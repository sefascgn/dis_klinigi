{{-- Büyük hasta listelerinde de çalışan, sunucuda arama yapan hasta seçici. --}}
@props(['name' => 'patient_id', 'patient' => null, 'label' => 'Hasta', 'required' => true, 'locked' => false])

@php
    $selectedId = old($name, $patient?->id);
    if ($selectedId && $patient?->id != $selectedId) {
        $patient = \App\Models\Patient::find($selectedId);
    }
    $initial = $patient ? ['id' => $patient->id, 'name' => $patient->full_name, 'phone' => $patient->phone] : null;
@endphp

<div x-data="patientPicker(@js($initial), @js(route('patients.search')))" {{ $attributes->only('class') }}>
    <label class="form-label">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>

    <input type="hidden" name="{{ $name }}" :value="selected ? selected.id : ''">

    <template x-if="selected">
        <div class="flex items-center justify-between gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-gray-900" x-text="selected.name">{{ $initial['name'] ?? '' }}</p>
                <p class="text-xs text-gray-500" x-text="selected.phone">{{ $initial['phone'] ?? '' }}</p>
            </div>
            @unless ($locked)
                <button type="button" class="btn-ghost btn-sm" @click="clear()">Değiştir</button>
            @endunless
        </div>
    </template>

    <div x-show="!selected" class="relative" @click.outside="open = false">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="open = results.length > 0"
               placeholder="Ad, telefon veya T.C. no ile arayın (en az 2 karakter)"
               class="form-input pl-9 @error($name) border-red-400 @enderror" autocomplete="off">

        <div x-show="open" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
            <template x-for="p in results" :key="p.id">
                <button type="button" @click="choose(p)" class="block w-full px-3 py-2 text-left hover:bg-primary-50">
                    <span class="block text-sm font-medium text-gray-900" x-text="p.name"></span>
                    <span class="block text-xs text-gray-500" x-text="p.phone"></span>
                </button>
            </template>
            <p x-show="!loading && results.length === 0" class="px-3 py-2 text-sm text-gray-500">Hasta bulunamadı.</p>
        </div>
    </div>

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
