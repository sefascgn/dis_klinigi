{{--
    Sıfır çizgisinden yukarı (kâr) / aşağı (zarar) sütunlar.
    rows: [['label' => 'Oca', 'tooltip' => 'Ocak 2026', <key> => float], ...]
--}}
@props(['rows', 'key' => 'net', 'height' => 200])

@php
    use App\Support\ChartScale;
    use App\Support\Money;

    $values = collect($rows)->pluck($key);
    $scale = ChartScale::make((float) $values->min(), (float) $values->max());
    $zero = ChartScale::percent(0, $scale);
    $last = count($rows) - 1;
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="mb-3 flex flex-wrap gap-4 text-xs text-gray-600">
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background: var(--series-1)"></span>Kâr</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background: var(--series-negative)"></span>Zarar</span>
    </div>

    <div class="relative" style="height: {{ $height }}px">
        @foreach ($scale['ticks'] as $tick)
            @php $bottom = ChartScale::percent($tick, $scale); @endphp
            <span class="absolute left-0 w-14 -translate-y-1/2 pr-2 text-right text-[11px] tabular-nums text-gray-400" style="top: {{ 100 - $bottom }}%">{{ Money::compact($tick) }}</span>
            <div class="absolute left-14 right-0 border-t" style="top: {{ 100 - $bottom }}%; border-color: {{ $tick == 0 ? 'var(--chart-baseline)' : 'var(--chart-grid)' }}"></div>
        @endforeach

        <div class="absolute inset-y-0 left-14 right-0 flex">
            @foreach ($rows as $i => $row)
                @php
                    $value = $row[$key];
                    $pct = ChartScale::percent($value, $scale);
                @endphp
                <div tabindex="0" class="group relative h-full min-w-0 flex-1 outline-none focus-visible:bg-gray-50" aria-label="{{ $row['label'] }}: {{ Money::format($value) }}">
                    <div class="pointer-events-none absolute inset-0 rounded bg-gray-100/70 opacity-0 transition group-hover:opacity-100"></div>
                    @if ($value != 0)
                        <div @class([
                                'absolute left-1/2 w-[40%] max-w-6 -translate-x-1/2',
                                'rounded-t-[4px]' => $value > 0,
                                'rounded-b-[4px]' => $value < 0,
                             ])
                             style="background: {{ $value > 0 ? 'var(--series-1)' : 'var(--series-negative)' }};
                                    bottom: {{ $value > 0 ? $zero : $pct }}%;
                                    height: max(2px, {{ abs($pct - $zero) }}%)"></div>
                    @endif

                    <div @class([
                        'pointer-events-none absolute z-20 hidden whitespace-nowrap rounded-lg bg-white px-3 py-2 text-xs shadow-lg ring-1 ring-black/10 group-hover:block group-focus:block',
                        'left-0' => $i < 2,
                        'right-0' => $i > $last - 2,
                        'left-1/2 -translate-x-1/2' => $i >= 2 && $i <= $last - 2,
                    ]) style="bottom: calc({{ max($zero, $pct) }}% + 6px)">
                        <p class="mb-0.5 font-medium text-gray-500">{{ $row['tooltip'] ?? $row['label'] }}</p>
                        <p><span @class(['font-semibold tabular-nums', 'text-red-600' => $value < 0, 'text-gray-900' => $value >= 0])>{{ Money::format($value) }}</span>
                            <span class="text-gray-500">{{ $value < 0 ? 'zarar' : 'net kâr' }}</span></p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="ml-14 mt-1.5 flex">
        @foreach ($rows as $row)
            <span class="flex-1 text-center text-[11px] text-gray-500">{{ $row['label'] }}</span>
        @endforeach
    </div>
</div>
