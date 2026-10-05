<x-app-layout>
    <x-slot name="title">Klinik Bilgileri</x-slot>
    <x-slot name="header">
        <x-page-title title="Klinik bilgileri" subtitle="Panel başlığında ve randevu varsayılanlarında kullanılır" />
    </x-slot>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('clinic-settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Genel bilgiler</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="name" label="Klinik adı" :value="$setting->name" required class="sm:col-span-2" />
                    <x-form.input name="phone" label="Telefon" type="tel" :value="$setting->phone" />
                    <x-form.input name="email" label="E-posta" type="email" :value="$setting->email" />
                    <x-form.textarea name="address" label="Adres" :value="$setting->address" rows="2" class="sm:col-span-2" />
                    <x-form.input name="tax_office" label="Vergi dairesi" :value="$setting->tax_office" />
                    <x-form.input name="tax_number" label="Vergi numarası" :value="$setting->tax_number" inputmode="numeric" maxlength="11" />
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Çalışma ve randevu ayarları</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="opening_time" label="Açılış saati" type="time" :value="substr($setting->opening_time, 0, 5)" required />
                    <x-form.input name="closing_time" label="Kapanış saati" type="time" :value="substr($setting->closing_time, 0, 5)" required />
                    <x-form.input name="default_appointment_duration" label="Varsayılan randevu süresi (dk)" type="number" min="5" max="480"
                                  :value="$setting->default_appointment_duration" required />
                    <x-form.input name="recall_months" label="Kontrol aralığı (ay)" type="number" min="1" max="24"
                                  :value="$setting->recall_months" required
                                  hint="Son ziyaretten bu kadar ay sonra hasta “Kontrolü gelenler” listesine düşer." />
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 pt-5">
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </x-card>
</x-app-layout>
