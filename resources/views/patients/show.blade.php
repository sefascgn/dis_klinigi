<x-app-layout>
    <x-slot name="title">{{ $patient->full_name }}</x-slot>
    <x-slot name="header">
        <x-page-title :title="$patient->full_name" :subtitle="'Hasta no: #'.$patient->id.' · Kayıt: '.$patient->created_at->format('d.m.Y')">
            {{-- Yazdırılabilir belgeler --}}
            <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                <button type="button" @click="open = !open" class="btn-secondary">
                    <x-icon name="printer" class="h-4 w-4" /><span class="hidden sm:inline">Yazdır</span>
                </button>
                <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 z-30 mt-1 w-56 rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-lg">
                    <a href="{{ route('print.plan', $patient) }}" target="_blank" class="block px-4 py-2 hover:bg-gray-50">Tedavi planı / fiyat teklifi</a>
                    @can('viewAny', App\Models\Payment::class)
                        <a href="{{ route('print.statement', $patient) }}" target="_blank" class="block px-4 py-2 hover:bg-gray-50">Hesap ekstresi</a>
                    @endcan
                </div>
            </div>
            @can('update', $patient)
                <a href="{{ route('patients.edit', $patient) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" /><span class="hidden sm:inline">Düzenle</span></a>
            @endcan
            @can('delete', $patient)
                <x-delete-button :action="route('patients.destroy', $patient)" class="btn-secondary !text-red-600"
                                 confirm="{{ $patient->full_name }} kaydı silinsin mi? Geçmiş kayıtlar arşivde korunur." />
            @endcan
        </x-page-title>
    </x-slot>

    @php
        $canSeePayments = auth()->user()->can('viewAny', App\Models\Payment::class);

        // Hasta geçmişi: randevu, tedavi ve ödemeleri tek bir zaman çizelgesinde birleştir.
        $timeline = collect()
            ->merge($patient->appointments->map(fn ($a) => ['date' => $a->starts_at, 'type' => 'appointment', 'item' => $a]))
            ->merge($patient->treatments->map(fn ($t) => ['date' => $t->treatment_date, 'type' => 'treatment', 'item' => $t]))
            ->when($canSeePayments, fn ($c) => $c->merge($patient->payments->map(fn ($p) => ['date' => $p->paid_at, 'type' => 'payment', 'item' => $p])))
            ->sortByDesc('date')
            ->values();
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Sol kolon: hasta bilgileri --}}
        <div class="space-y-6">
            @if ($patient->hasMedicalAlerts())
                <div class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <x-icon name="warning" class="h-5 w-5 text-red-500" />
                    <div class="min-w-0 space-y-2">
                        <p class="font-semibold">Sağlık uyarısı</p>
                        @if ($patient->allergies)
                            <p class="whitespace-pre-line"><span class="font-medium">Alerji:</span> {{ $patient->allergies }}</p>
                        @endif
                        @if ($patient->medical_conditions?->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($patient->medical_conditions as $condition)
                                    <x-badge :color="$condition->isCritical() ? 'red' : 'yellow'">{{ $condition->label() }}</x-badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <x-card title="İletişim ve kimlik">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Telefon</dt><dd class="flex items-center justify-end gap-2 text-right font-medium">
                        <a href="tel:{{ $patient->phone }}" class="link">{{ $patient->formatted_phone }}</a>
                        @if ($wa = App\Support\WhatsApp::link($patient->phone, 'Merhaba '.$patient->first_name.', '))
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="text-emerald-600 hover:text-emerald-700" title="WhatsApp'tan yaz"><x-icon name="chat" class="h-4 w-4" /></a>
                        @endif
                    </dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">E-posta</dt><dd class="truncate text-right">{{ $patient->email ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">T.C. Kimlik No</dt><dd class="text-right">{{ $patient->national_id ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Doğum tarihi</dt><dd class="text-right">{{ $patient->birth_date ? $patient->birth_date->format('d.m.Y').' ('.$patient->age.' yaş)' : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Cinsiyet</dt><dd class="text-right">{{ $patient->gender?->label() ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Adres</dt><dd class="mt-1 whitespace-pre-line">{{ $patient->address ?? '—' }}</dd></div>
                    @if ($patient->medical_notes)
                        <div><dt class="text-gray-500">Tıbbi notlar</dt><dd class="mt-1 whitespace-pre-line">{{ $patient->medical_notes }}</dd></div>
                    @endif
                </dl>
            </x-card>

            @if ($canSeePayments)
                <x-card title="Hesap özeti">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Toplam tedavi tutarı</dt><dd class="font-medium">@money($patient->totalTreatmentAmount())</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Ödenen</dt><dd class="font-medium text-emerald-700">@money($patient->totalPaid())</dd></div>
                        <div class="flex justify-between border-t border-gray-100 pt-3">
                            <dt class="font-semibold text-gray-900">{{ $patient->balance() < 0 ? 'Hasta alacağı' : 'Kalan borç' }}</dt>
                            <dd @class(['text-base font-semibold', 'text-red-600' => $patient->balance() > 0, 'text-gray-900' => $patient->balance() <= 0])>@money(abs($patient->balance()))</dd>
                        </div>
                    </dl>
                    @can('create', App\Models\Payment::class)
                        <a href="{{ route('payments.create', ['patient_id' => $patient->id]) }}" class="btn-primary mt-4 w-full"><x-icon name="plus" class="h-4 w-4" />Ödeme al</a>
                    @endcan
                </x-card>
            @endif
        </div>

        {{-- Sağ kolon: geçmiş sekmeleri --}}
        @php
            $tabs = ['history' => 'Geçmiş', 'appointments' => 'Randevular ('.$patient->appointments->count().')', 'treatments' => 'Tedaviler ('.$patient->treatments->count().')', 'chart' => 'Diş şeması'];
            if ($canSeePayments) {
                $tabs['payments'] = 'Ödemeler ('.$patient->payments->count().')';
            }
            $tabs['documents'] = 'Belgeler ('.$patient->documents->count().')';
            // Yükleme vb. işlemlerden sonra ilgili sekmeye geri dönülür (?tab=documents).
            $initialTab = array_key_exists(request('tab'), $tabs) ? request('tab') : 'history';
        @endphp
        <div class="xl:col-span-2" x-data="{ tab: @js($initialTab) }">

            <div class="mb-4 flex gap-1 overflow-x-auto rounded-lg border border-gray-200 bg-white p-1">
                @foreach ($tabs as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                            class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium transition">{{ $label }}</button>
                @endforeach
            </div>

            {{-- Geçmiş --}}
            <x-card x-show="tab === 'history'" title="Hasta geçmişi">
                @if ($timeline->isEmpty())
                    <p class="py-6 text-center text-sm text-gray-500">Henüz kayıt yok.</p>
                @else
                    <ol class="relative space-y-5 border-l border-gray-200 pl-6">
                        @foreach ($timeline as $event)
                            @php $item = $event['item']; @endphp
                            <li class="relative">
                                <span @class([
                                    'absolute -left-[33px] flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white',
                                    'bg-sky-100 text-sky-600' => $event['type'] === 'appointment',
                                    'bg-primary-100 text-primary-600' => $event['type'] === 'treatment',
                                    'bg-emerald-100 text-emerald-600' => $event['type'] === 'payment',
                                ])>
                                    <x-icon :name="['appointment' => 'calendar', 'treatment' => 'tooth', 'payment' => 'cash'][$event['type']]" class="h-3.5 w-3.5" />
                                </span>
                                <p class="text-xs text-gray-500">{{ $event['date']->format($event['type'] === 'appointment' ? 'd.m.Y H:i' : 'd.m.Y') }}</p>
                                @switch($event['type'])
                                    @case('appointment')
                                        <p class="text-sm"><span class="font-medium">Randevu</span> · {{ $item->doctor->full_name }} <x-status-badge :status="$item->status" /></p>
                                        @if ($item->notes)<p class="mt-0.5 text-sm text-gray-600">{{ $item->notes }}</p>@endif
                                        @break
                                    @case('treatment')
                                        <p class="text-sm"><span class="font-medium">{{ $item->treatmentType->name }}</span>@if ($item->tooth_number) · Diş {{ $item->tooth_number }}@endif · {{ $item->doctor->full_name }} <x-status-badge :status="$item->status" /></p>
                                        @if ($item->notes)<p class="mt-0.5 text-sm text-gray-600">{{ $item->notes }}</p>@endif
                                        @break
                                    @case('payment')
                                        <p class="text-sm"><span class="font-medium">Ödeme</span> · @money($item->amount) · {{ $item->method->label() }}</p>
                                        @break
                                @endswitch
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-card>

            {{-- Randevular --}}
            <x-card x-show="tab === 'appointments'" x-cloak title="Randevular" :padding="false">
                <x-slot name="actions">
                    @can('create', App\Models\Appointment::class)
                        <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />Randevu oluştur</a>
                    @endcan
                </x-slot>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Tarih</th><th>Doktor</th><th>Durum</th><th class="hidden md:table-cell">Not</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($patient->appointments as $appointment)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $appointment->starts_at->format('d.m.Y H:i') }}</td>
                                    <td>{{ $appointment->doctor->full_name }}</td>
                                    <td>
                                        <x-status-badge :status="$appointment->status" />
                                        @if ($appointment->cancellation_reason)<p class="mt-0.5 text-xs text-gray-500">{{ $appointment->cancellation_reason }}</p>@endif
                                    </td>
                                    <td class="hidden max-w-xs truncate md:table-cell">{{ $appointment->notes ?? '—' }}</td>
                                    <td class="text-right">
                                        @can('view', $appointment)
                                            <x-appointment-actions :appointment="$appointment" />
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <x-empty-row colspan="5" message="Randevu kaydı yok." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- Tedaviler --}}
            <x-card x-show="tab === 'treatments'" x-cloak title="Uygulanan tedaviler" :padding="false">
                <x-slot name="actions">
                    @can('create', App\Models\Treatment::class)
                        <a href="{{ route('treatments.create', ['patient_id' => $patient->id]) }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />Tedavi ekle</a>
                    @endcan
                </x-slot>
                @include('treatments._table', ['treatments' => $patient->treatments, 'showPatient' => false])
            </x-card>

            {{-- Diş şeması --}}
            <x-card x-show="tab === 'chart'" x-cloak title="Diş şeması (FDI)">
                <x-dental-chart :treatments="$patient->treatments" />
            </x-card>

            {{-- Ödemeler --}}
            @if ($canSeePayments)
                <x-card x-show="tab === 'payments'" x-cloak title="Ödeme geçmişi" :padding="false">
                    <x-slot name="actions">
                        @can('create', App\Models\Payment::class)
                            <a href="{{ route('payments.create', ['patient_id' => $patient->id]) }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />Ödeme al</a>
                        @endcan
                    </x-slot>
                    @include('payments._table', ['payments' => $patient->payments, 'showPatient' => false])
                </x-card>
            @endif

            {{-- Belgeler: röntgen, fotoğraf, onam formu --}}
            <x-card x-show="tab === 'documents'" x-cloak title="Belgeler">
                @can('create', App\Models\PatientDocument::class)
                    <form method="POST" action="{{ route('patients.documents.store', $patient) }}" enctype="multipart/form-data"
                          class="mb-5 grid grid-cols-1 gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 sm:grid-cols-6 sm:items-end">
                        @csrf
                        <x-form.select name="type" label="Tür" :options="App\Enums\DocumentType::options()" value="xray" class="sm:col-span-2" />
                        <x-form.input name="title" label="Başlık" placeholder="Örn. Panoramik röntgen" maxlength="150" class="sm:col-span-4" />
                        <div class="sm:col-span-4">
                            <label for="file" class="form-label">Dosya <span class="text-red-500">*</span></label>
                            <input type="file" id="file" name="file" required accept=".jpg,.jpeg,.png,.webp,.pdf"
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100">
                            <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP veya PDF · en fazla 10 MB</p>
                            @error('file')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="btn-primary sm:col-span-2"><x-icon name="upload" class="h-4 w-4" />Yükle</button>
                    </form>
                @endcan

                @if ($patient->documents->isEmpty())
                    <p class="py-6 text-center text-sm text-gray-500">Henüz belge yüklenmedi.</p>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($patient->documents as $document)
                            <div class="group overflow-hidden rounded-lg border border-gray-200 bg-white">
                                <a href="{{ route('documents.show', $document) }}" target="_blank" class="flex aspect-[4/3] items-center justify-center bg-gray-100">
                                    @if ($document->isImage())
                                        <img src="{{ route('documents.show', $document) }}" alt="{{ $document->title }}" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex flex-col items-center gap-1 text-gray-400"><x-icon name="document" class="h-10 w-10" /><span class="text-xs font-semibold">PDF</span></span>
                                    @endif
                                </a>
                                <div class="p-2">
                                    <p class="truncate text-sm font-medium text-gray-900" title="{{ $document->title }}">{{ $document->title }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $document->type->label() }} · {{ $document->created_at->format('d.m.Y') }} · {{ $document->humanSize() }}</p>
                                    <div class="mt-1 flex items-center justify-between">
                                        <a href="{{ route('documents.show', [$document, 'download' => 1]) }}" class="text-xs font-medium text-primary-700 hover:underline">İndir</a>
                                        @can('delete', $document)
                                            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Belge silinsin mi?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Sil</button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
