<x-app-layout>
    <x-slot name="title">Gösterge Paneli</x-slot>
    <x-slot name="header">
        <x-page-title title="Gösterge Paneli" :subtitle="today()->translatedFormat('j F Y, l')">
            @can('create', App\Models\Patient::class)
                <a href="{{ route('patients.create') }}" class="btn-secondary hidden sm:inline-flex"><x-icon name="plus" class="h-4 w-4" />Yeni hasta</a>
            @endcan
            @can('create', App\Models\Appointment::class)
                <a href="{{ route('appointments.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Yeni randevu</a>
            @endcan
        </x-page-title>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Bugünkü randevular" :value="$stats['today']" icon="calendar" :href="route('appointments.index')" />
        <x-stat-card label="Bekleyen randevular" :value="$stats['pending']" icon="clock" color="yellow"
                     :href="route('appointments.index', ['view' => 'list', 'status' => 'pending'])" />
        <x-stat-card label="Bugün tamamlanan" :value="$stats['completed_today']" icon="check" color="green" />
        <x-stat-card label="Toplam hasta" :value="number_format($stats['patients'], 0, ',', '.')" icon="users" color="blue" :href="route('patients.index')" />
    </div>

    @if ($finance)
        {{-- Yönetici: bu ay ne geldi, ne gitti, ne kaldı --}}
        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 xl:col-span-2 xl:grid-cols-1">
                @php $net = $finance['totals']['net']['value']; @endphp
                <x-finance-tile :label="'Bu ay net ('.today()->translatedFormat('F').')'" :value="App\Support\Money::format($net)" :negative="$net < 0"
                                :change="$finance['totals']['net']['change']" :compare="$finance['compareLabel']" class="ring-1 ring-primary-200" />
                <x-finance-tile label="Bu ay gelen" :value="App\Support\Money::format($finance['totals']['collected']['value'])" :change="$finance['totals']['collected']['change']" :compare="$finance['compareLabel']" />
                <x-finance-tile label="Bu ay giden" :value="App\Support\Money::format($finance['totals']['expenses']['value'])" :change="$finance['totals']['expenses']['change']" :compare="$finance['compareLabel']" :up-is-good="false" />
            </div>
            <x-card title="Son 6 ay gelen ve giden" class="xl:col-span-3">
                <x-slot name="actions"><a href="{{ route('reports.index') }}" class="link text-sm">Detaylı rapor</a></x-slot>
                <x-charts.column-chart :rows="$finance['monthly']" :height="250"
                    :series="[
                        ['key' => 'collected', 'label' => 'Gelen (tahsilat)', 'color' => 'var(--series-1)'],
                        ['key' => 'expenses', 'label' => 'Giden (gider)', 'color' => 'var(--series-2)'],
                    ]"
                    :extra="[['key' => 'net', 'label' => 'Net']]" />
            </x-card>
        </div>
    @elseif ($revenue)
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card label="Bugünkü gelir" :value="App\Support\Money::format($revenue['daily'])" icon="cash" color="purple"
                         :href="route('payments.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()])" />
            <x-stat-card label="Bu ayki gelir ({{ today()->translatedFormat('F') }})" :value="App\Support\Money::format($revenue['monthly'])" icon="cash" color="purple"
                         :href="route('payments.index')" />
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
        <x-card title="Bugünün programı" :padding="false" class="xl:col-span-3">
            <x-slot name="actions">
                <a href="{{ route('appointments.index') }}" class="link text-sm">Tümünü gör</a>
            </x-slot>

            <ul class="divide-y divide-gray-100">
                @forelse ($todaySchedule as $appointment)
                    <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                        <div class="w-24 shrink-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $appointment->starts_at->format('H:i') }}</p>
                            <p class="text-xs text-gray-500">{{ $appointment->durationInMinutes() }} dk</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('patients.show', $appointment->patient_id) }}" class="link block truncate">{{ $appointment->patient->full_name }}</a>
                            <p class="truncate text-xs text-gray-500">{{ $appointment->doctor->full_name }}</p>
                        </div>
                        <x-status-badge :status="$appointment->status" />
                        <x-appointment-actions :appointment="$appointment" />
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-gray-500">Bugün için randevu yok.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card title="Yaklaşan randevular" :padding="false" class="xl:col-span-2">
            <ul class="divide-y divide-gray-100">
                @forelse ($upcoming as $appointment)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-primary-50 text-primary-700">
                            <span class="text-sm font-semibold leading-none">{{ $appointment->starts_at->format('d') }}</span>
                            <span class="text-[10px] uppercase">{{ $appointment->starts_at->translatedFormat('M') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('patients.show', $appointment->patient_id) }}" class="link block truncate text-sm">{{ $appointment->patient->full_name }}</a>
                            <p class="truncate text-xs text-gray-500">{{ $appointment->starts_at->translatedFormat('l H:i') }} · {{ $appointment->doctor->full_name }}</p>
                        </div>
                        <x-status-badge :status="$appointment->status" />
                        @if ($wa = App\Support\WhatsApp::appointmentReminder($appointment))
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="rounded p-1 text-emerald-600 hover:bg-emerald-50" title="WhatsApp ile hatırlat">
                                <x-icon name="chat" class="h-4 w-4" />
                            </a>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-gray-500">Yaklaşan randevu yok.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-app-layout>
