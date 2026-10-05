{{--
    Gruplu sütun grafiği (tek eksen, negatif olmayan değerler).
    rows:   [['label' => 'Oca', ...değerler], ...]
    series: [['key' => 'collected', 'label' => 'Tahsilat', 'color' => 'var(--series-1)'], ...]
    extra:  tooltip'te ek satırlar: [['key' => 'net', 'label' => 'Net']]
--}}
@props(['rows', 'series', 'extra' => [], 'height' => 240])

@php
    use App\Support\ChartScale;
    use App\Support\Money;

    $max = collect($rows)->flatMap(fn ($row) => collect($series)->map(fn ($s) => $row[$s['key']]))->max() ?? 0;
    $scale = ChartScale::make(0, (float) $max);
    $last = count($rows) - 1;
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    {{-- Lejant (2+ seri) --}}
    @if (count($series) > 1)
        <div class="mb-3 flex flex-wrap gap-4 text-xs text-gray-600">
            @foreach ($series as $s)
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $s['color'] }}"></span>{{ $s['label'] }}</span>
            @endforeach
        </div>
    @endif

    <div class="relative" style="height: {{ $height }}px">
        {{-- Y ekseni etiketleri ve kılavuz çizgileri --}}
        @foreach ($scale['ticks'] as $tick)
            @php $bottom = ChartScale::percent($tick, $scale); @endphp
            <span class="absolute left-0 w-14 -translate-y-1/2 pr-2 text-right text-[11px] tabular-nums text-gray-400" style="top: {{ 100 - $bottom }}%">{{ Money::compact($tick) }}</span>
            <div class="absolute left-14 right-0 border-t" style="top: {{ 100 - $bottom }}%; border-color: {{ $tick == 0 ? 'var(--chart-baseline)' : 'var(--chart-grid)' }}"></div>
        @endforeach

        {{-- Sütunlar --}}
        <div class="absolute inset-y-0 left-14 right-0 flex">
            @foreach ($rows as $i => $row)
                <div tabindex="0" class="group relative flex h-full min-w-0 flex-1 items-end justify-center gap-[2px] px-1 outline-none focus-visible:bg-gray-50 sm:px-2"
                     aria-label="{{ $row['label'] }}: {{ collect($series)->map(fn ($s) => $s['label'].' '.Money::format($row[$s['key']]))->implode(', ') }}">
                    <div class="pointer-events-none absolute inset-0 rounded bg-gray-100/70 opacity-0 transition group-hover:opacity-100"></div>
                    @foreach ($series as $s)
                        <div class="relative max-w-6 flex-1 rounded-t-[4px] transition"
                             style="height: {{ ChartScale::percent($row[$s['key']], $scale) }}%; background: {{ $s['color'] }}; {{ $row[$s['key']] > 0 ? 'min-height: 2px;' : '' }}"></div>
                    @endforeach

                    {{-- Tooltip: değer önde, etiket arkada --}}
                    <div @class([
                        'pointer-events-none absolute top-1 z-20 hidden min-w-44 whitespace-nowrap rounded-lg bg-white px-3 py-2 text-xs shadow-lg ring-1 ring-black/10 group-hover:block group-focus:block',
                        'left-0' => $i < 2,
                        'right-0' => $i > $last - 2,
                        'left-1/2 -translate-x-1/2' => $i >= 2 && $i <= $last - 2,
                    ])>
                        <p class="mb-1 font-medium text-gray-500">{{ $row['tooltip'] ?? $row['label'] }}</p>
                        @foreach ($series as $s)
                            <p class="flex items-center gap-2">
                                <span class="h-0.5 w-3 rounded" style="background: {{ $s['color'] }}"></span>
                                <span class="font-semibold tabular-nums text-gray-900">{{ Money::format($row[$s['key']]) }}</span>
                                <span class="text-gray-500">{{ $s['label'] }}</span>
                            </p>
                        @endforeach
                        @foreach ($extra as $e)
                            <p class="mt-1 flex items-center gap-2 border-t border-gray-100 pt-1">
                                <span class="w-3"></span>
                                <span @class(['font-semibold tabular-nums', 'text-red-600' => $row[$e['key']] < 0, 'text-gray-900' => $row[$e['key']] >= 0])>{{ Money::format($row[$e['key']]) }}</span>
                                <span class="text-gray-500">{{ $e['label'] }}</span>
                            </p>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- X ekseni --}}
    <div class="ml-14 mt-1.5 flex">
        @foreach ($rows as $row)
            <span class="flex-1 text-center text-[11px] text-gray-500">{{ $row['label'] }}</span>
        @endforeach
    </div>
</div>
