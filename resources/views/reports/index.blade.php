<x-app-layout>
    <x-slot name="title">Raporlar</x-slot>
    <x-slot name="header">
        <x-page-title title="Raporlar" :subtitle="$from->format('d.m.Y').' – '.$to->format('d.m.Y')">
            @can('create', App\Models\Expense::class)
                <a href="{{ route('expenses.create') }}" class="btn-secondary"><x-icon name="plus" class="h-4 w-4" />Gider ekle</a>
            @endcan
        </x-page-title>
    </x-slot>

    @php
        $money = fn ($v) => App\Support\Money::format($v);
        $presets = [
            'Bu ay' => [today()->startOfMonth(), today()],
            'Geçen ay' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            'Son 3 ay' => [today()->subMonths(3)->addDay(), today()],
            'Bu yıl' => [today()->startOfYear(), today()],
            'Geçen yıl' => [today()->subYear()->startOfYear(), today()->subYear()->endOfYear()],
        ];
        $net = $totals['net']['value'];
        $margin = $totals['collected']['value'] > 0 ? round($net / $totals['collected']['value'] * 100) : null;
    @endphp

    {{-- Dönem seçimi: tüm sayılar ve dağılımlar bu aralığa göre --}}
    <form method="GET" class="mb-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="inline-flex flex-wrap rounded-lg border border-gray-200 bg-white p-0.5">
            @foreach ($presets as $label => [$pFrom, $pTo])
                @php $active = $from->isSameDay($pFrom) && $to->isSameDay($pTo); @endphp
                <a href="{{ route('reports.index', ['from' => $pFrom->toDateString(), 'to' => $pTo->toDateString()]) }}"
                   @class(['rounded-md px-3 py-1.5 text-sm font-medium', 'bg-primary-600 text-white' => $active, 'text-gray-600 hover:bg-gray-100' => ! $active])>{{ $label }}</a>
            @endforeach
        </div>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-input sm:w-40" aria-label="Başlangıç">
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-input sm:w-40" aria-label="Bitiş">
        <button type="submit" class="btn-secondary">Uygula</button>
        @error('to')<p class="form-error">{{ $message }}</p>@enderror
    </form>

    {{-- Ana finans kartları --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-finance-tile label="Net kâr (tahsilat − gider)" :value="$money($net)" :negative="$net < 0" :change="$totals['net']['change']" :compare="$compareLabel"
                        :hint="$margin !== null ? 'Kâr marjı: %'.$margin : null" class="ring-1 ring-primary-200" />
        <x-finance-tile label="Gelen para (tahsilat)" :value="$money($totals['collected']['value'])" :change="$totals['collected']['change']" :compare="$compareLabel" />
        <x-finance-tile label="Giden para (gider)" :value="$money($totals['expenses']['value'])" :change="$totals['expenses']['change']" :compare="$compareLabel" :up-is-good="false" />
        <x-finance-tile label="Ciro (tamamlanan tedavi)" :value="$money($totals['revenue']['value'])" :change="$totals['revenue']['change']" :compare="$compareLabel"
                        hint="Yapılan işin değeri; tahsil edilmemiş kısım alacağa yazılır." />
    </div>

    {{-- Aylık gelir – gider --}}
    <x-card title="Aylık gelen ve giden para (son 12 ay)" class="mt-6">
        <x-charts.column-chart :rows="$monthly->all()" :height="260"
            :series="[
                ['key' => 'collected', 'label' => 'Gelen (tahsilat)', 'color' => 'var(--series-1)'],
                ['key' => 'expenses', 'label' => 'Giden (gider)', 'color' => 'var(--series-2)'],
            ]"
            :extra="[['key' => 'net', 'label' => 'Net'], ['key' => 'revenue', 'label' => 'Ciro']]" />
    </x-card>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
        <x-card title="Aylık net kâr / zarar" class="xl:col-span-3">
            <x-charts.net-chart :rows="$monthly->all()" />
        </x-card>

        <x-card title="Operasyon" class="xl:col-span-2">
            <dl class="divide-y divide-gray-100 text-sm">
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Toplam alacak <span class="text-xs">({{ $operations['debtors'] }} hasta)</span></dt>
                    <dd><a href="{{ route('patients.index', ['filter' => 'debtors']) }}" class="font-semibold text-gray-900 hover:text-primary-700">{{ $money($operations['receivable']) }}</a></dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Kontrolü gelen hasta</dt>
                    <dd><a href="{{ route('patients.index', ['filter' => 'recall']) }}" class="font-semibold text-gray-900 hover:text-primary-700">{{ $operations['recall'] }}</a></dd>
                </div>
                <div class="flex items-center justify-between py-2.5"><dt class="text-gray-500">Yeni hasta</dt><dd class="font-semibold">{{ $operations['new_patients'] }}</dd></div>
                <div class="flex items-center justify-between py-2.5"><dt class="text-gray-500">Randevu</dt><dd class="font-semibold">{{ $operations['appointments'] }}</dd></div>
                <div class="flex items-center justify-between py-2.5"><dt class="text-gray-500">İptal oranı</dt><dd class="font-semibold">%{{ $operations['cancel_rate'] }}</dd></div>
            </dl>
        </x-card>
    </div>

    {{-- Dağılımlar (seçili dönem) --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Giderler nereye gitti?">
            <x-slot name="actions"><a href="{{ route('expenses.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="link text-sm">Giderler</a></x-slot>
            <x-charts.bar-list :items="$expensesByCategory" color="var(--series-2)" empty="Bu dönemde gider kaydı yok." />
        </x-card>
        <x-card title="Ciro: işlem bazında">
            <x-charts.bar-list :items="$revenueByType" empty="Bu dönemde tamamlanan tedavi yok." />
        </x-card>
        <x-card title="Ciro: doktor bazında">
            <x-charts.bar-list :items="$revenueByDoctor" empty="Bu dönemde tamamlanan tedavi yok." />
        </x-card>
        <x-card title="Tahsilat yöntemleri">
            <x-charts.bar-list :items="$collectedByMethod" empty="Bu dönemde ödeme yok." />
        </x-card>
    </div>

    {{-- Tablo görünümü: grafiklerdeki tüm değerler --}}
    <x-card title="Aylık özet tablosu" :padding="false" class="mt-6">
        <div class="table-wrap">
            <table class="table tabular-nums">
                <thead>
                    <tr><th>Ay</th><th class="text-right">Ciro</th><th class="text-right">Gelen</th><th class="text-right">Giden</th><th class="text-right">Net</th><th class="text-right">Marj</th></tr>
                </thead>
                <tbody>
                    @foreach ($monthly->reverse() as $row)
                        <tr>
                            <td class="whitespace-nowrap">{{ $row['tooltip'] }}</td>
                            <td class="whitespace-nowrap text-right">{{ $money($row['revenue']) }}</td>
                            <td class="whitespace-nowrap text-right">{{ $money($row['collected']) }}</td>
                            <td class="whitespace-nowrap text-right">{{ $money($row['expenses']) }}</td>
                            <td @class(['whitespace-nowrap text-right font-semibold', 'text-red-600' => $row['net'] < 0])>{{ $money($row['net']) }}</td>
                            <td class="whitespace-nowrap text-right text-gray-500">{{ $row['collected'] > 0 ? '%'.round($row['net'] / $row['collected'] * 100) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                    @php $sumNet = $monthly->sum('net'); @endphp
                    <tr>
                        <td class="px-4 py-3">12 ay toplam</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">{{ $money($monthly->sum('revenue')) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">{{ $money($monthly->sum('collected')) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">{{ $money($monthly->sum('expenses')) }}</td>
                        <td @class(['whitespace-nowrap px-4 py-3 text-right', 'text-red-600' => $sumNet < 0])>{{ $money($sumNet) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500">{{ $monthly->sum('collected') > 0 ? '%'.round($sumNet / $monthly->sum('collected') * 100) : '—' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</x-app-layout>
