{{--
    Yatay çubuk listesi (tek seri, büyükten küçüğe): kategori dağılımları için.
    items: [['label' => 'Kira', 'value' => 25000.0, 'hint' => '3 kayıt'], ...]
--}}
@props(['items', 'color' => 'var(--series-1)', 'empty' => 'Bu aralıkta veri yok.'])

@php
    $items = collect($items)->sortByDesc('value')->values();
    $max = max(1, (float) $items->max('value'));
    $total = max(0.01, (float) $items->sum('value'));
@endphp

@if ($items->isEmpty())
    <p class="py-6 text-center text-sm text-gray-500">{{ $empty }}</p>
@else
    <ul {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @foreach ($items as $item)
            <li class="group" title="{{ $item['label'] }}: {{ App\Support\Money::format($item['value']) }} (%{{ number_format($item['value'] / $total * 100, 1, ',', '.') }})">
                <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                    <span class="min-w-0 truncate text-gray-700">
                        {{ $item['label'] }}
                        @if (! empty($item['hint']))<span class="text-xs text-gray-400">· {{ $item['hint'] }}</span>@endif
                    </span>
                    <span class="shrink-0 whitespace-nowrap">
                        <span class="font-semibold tabular-nums text-gray-900">@money($item['value'])</span>
                        <span class="ml-1 w-10 text-xs tabular-nums text-gray-400">%{{ number_format($item['value'] / $total * 100, 0) }}</span>
                    </span>
                </div>
                <div class="h-2 rounded-full bg-gray-100">
                    <div class="h-2 rounded-full transition group-hover:opacity-80" style="width: {{ max($item['value'] / $max * 100, 1) }}%; background: {{ $color }}"></div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
