<x-app-layout>
    <x-slot name="title">Hastalar</x-slot>
    <x-slot name="header">
        <x-page-title title="Hastalar" :subtitle="$patients->total().' kayıt'">
            @can('create', App\Models\Patient::class)
                <a href="{{ route('patients.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Yeni hasta</a>
            @endcan
        </x-page-title>
    </x-slot>

    @php
        $showBalance = auth()->user()->can('viewAny', App\Models\Payment::class);
        $isRecall = $filter === 'recall';

        $filters = ['' => 'Tüm hastalar'];
        if ($showBalance) {
            $filters['debtors'] = 'Borçlu hastalar';
        }
        $filters['recall'] = 'Kontrolü gelenler';
    @endphp

    <x-card :padding="false">
        <div class="space-y-3 border-b border-gray-100 p-4">
            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                @foreach ($filters as $key => $label)
                    <a href="{{ route('patients.index', array_filter(['filter' => $key, 'q' => request('q')])) }}"
                       @class([
                           'rounded-md px-3 py-1.5 text-sm font-medium transition',
                           'bg-primary-600 text-white shadow-sm' => (string) $filter === $key,
                           'text-gray-600 hover:bg-white' => (string) $filter !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </div>

            @if ($isRecall)
                <p class="text-sm text-gray-500">
                    Son tamamlanan randevusunun üzerinden en az {{ App\Models\ClinicSetting::current()->recall_months }} ay geçmiş ve ileri tarihli randevusu olmayan hastalar.
                    WhatsApp butonu kontrol hatırlatma mesajını hazır açar.
                </p>
            @endif

            <x-search-form placeholder="Ad, telefon, T.C. no, e-posta">
                @if ($filter)
                    <input type="hidden" name="filter" value="{{ $filter }}">
                @endif
            </x-search-form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Hasta</th>
                        <th>Telefon</th>
                        @if ($isRecall)
                            <th>Son ziyaret</th>
                        @else
                            <th class="hidden md:table-cell">T.C. Kimlik No</th>
                            <th class="hidden lg:table-cell">Yaş</th>
                        @endif
                        @if ($showBalance)
                            <th class="text-right">Bakiye</th>
                        @endif
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $patient)
                        <tr>
                            <td>
                                <a href="{{ route('patients.show', $patient) }}" class="link">{{ $patient->full_name }}</a>
                                @if ($patient->hasMedicalAlerts())
                                    <x-badge color="red" class="ml-1" title="{{ collect([$patient->allergies ? 'Alerji: '.$patient->allergies : null])->merge($patient->medical_conditions?->map->label())->filter()->implode(', ') }}">Sağlık uyarısı</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $patient->phone }}</td>
                            @if ($isRecall)
                                <td class="whitespace-nowrap">
                                    {{ Illuminate\Support\Carbon::parse($patient->last_visit_at)->format('d.m.Y') }}
                                    <span class="block text-xs text-gray-500">{{ Illuminate\Support\Carbon::parse($patient->last_visit_at)->diffForHumans() }}</span>
                                </td>
                            @else
                                <td class="hidden md:table-cell">{{ $patient->national_id ?? '—' }}</td>
                                <td class="hidden lg:table-cell">{{ $patient->age ?? '—' }}</td>
                            @endif
                            @if ($showBalance)
                                <td @class(['whitespace-nowrap text-right font-medium', 'text-red-600' => $patient->balance() > 0, 'text-gray-500' => $patient->balance() <= 0])>
                                    @money($patient->balance())
                                </td>
                            @endif
                            <td class="whitespace-nowrap text-right">
                                @if ($isRecall)
                                    @if ($wa = App\Support\WhatsApp::recallReminder($patient))
                                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm text-emerald-700 hover:bg-emerald-50 focus:ring-emerald-500" title="WhatsApp ile kontrol hatırlatması">
                                            <x-icon name="chat" class="h-4 w-4" /><span class="sr-only sm:not-sr-only">Hatırlat</span>
                                        </a>
                                    @endif
                                    @can('create', App\Models\Appointment::class)
                                        <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm text-gray-600 hover:bg-gray-100 focus:ring-primary-500" title="Randevu oluştur">
                                            <x-icon name="calendar" class="h-4 w-4" /><span class="sr-only sm:not-sr-only">Randevu</span>
                                        </a>
                                    @endcan
                                @else
                                    @can('update', $patient)
                                        <x-edit-link :href="route('patients.edit', $patient)" />
                                    @endcan
                                    @can('delete', $patient)
                                        <x-delete-button :action="route('patients.destroy', $patient)" confirm="{{ $patient->full_name }} kaydı silinsin mi? Geçmiş kayıtlar arşivde korunur." />
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="$showBalance ? 6 : 5" message="Hasta bulunamadı." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($patients->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $patients->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
