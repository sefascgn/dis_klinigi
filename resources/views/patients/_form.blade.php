@csrf

<div class="space-y-6">
    <div>
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Kimlik ve iletişim</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form.input name="first_name" label="Ad" :value="$patient->first_name" required maxlength="100" />
            <x-form.input name="last_name" label="Soyad" :value="$patient->last_name" required maxlength="100" />
            <x-form.input name="national_id" label="T.C. Kimlik No" :value="$patient->national_id" inputmode="numeric" maxlength="11" />
            <x-form.input name="birth_date" label="Doğum tarihi" type="date" :value="$patient->birth_date?->toDateString()" :max="today()->toDateString()" />
            <x-form.select name="gender" label="Cinsiyet" :options="App\Enums\Gender::options()" :value="$patient->gender" placeholder="Seçiniz" />
            <x-form.input name="phone" label="Telefon" type="tel" :value="$patient->phone" required placeholder="05xx xxx xx xx" />
            <x-form.input name="email" label="E-posta" type="email" :value="$patient->email" class="sm:col-span-2" />
            <x-form.textarea name="address" label="Adres" :value="$patient->address" rows="2" class="sm:col-span-2" />
        </div>
    </div>

    <div>
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Sağlık bilgileri (anamnez)</h3>
        @php
            $selectedConditions = collect(old('medical_conditions', $patient->medical_conditions?->map(fn ($c) => $c->value)->all() ?? []));
        @endphp
        <fieldset class="mb-4">
            <legend class="form-label">Sağlık durumu</legend>
            <div class="grid grid-cols-1 gap-2 rounded-lg border border-gray-200 p-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (App\Enums\MedicalCondition::cases() as $condition)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="medical_conditions[]" value="{{ $condition->value }}" @checked($selectedConditions->contains($condition->value))
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                        {{ $condition->label() }}
                    </label>
                @endforeach
            </div>
            @error('medical_conditions.*')<p class="form-error">{{ $message }}</p>@enderror
        </fieldset>
        <div class="grid grid-cols-1 gap-4">
            <x-form.textarea name="allergies" label="Alerjiler" :value="$patient->allergies" rows="2" placeholder="Örn. Penisilin, lateks" />
            <x-form.textarea name="medical_notes" label="Tıbbi notlar" :value="$patient->medical_notes" rows="4" placeholder="Kronik hastalıklar, kullanılan ilaçlar vb." />
        </div>
    </div>

    <x-form.actions :cancel="$patient->exists ? route('patients.show', $patient) : route('patients.index')" />
</div>
