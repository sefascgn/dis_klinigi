{{--
    Finans kartı: değer + önceki döneme göre değişim.
    upIsGood: artış iyi mi (gider için false) — renk = yön × iyi/kötü.
--}}
@props(['label', 'value', 'change' => null, 'upIsGood' => true, 'hint' => null, 'negative' => false, 'compare' => null])

@php
    $good = $change === null ? null : (($change >= 0) === $upIsGood);
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <p class="text-sm text-gray-500">{{ $label }}</p>
    <p @class(['mt-1 text-2xl font-semibold', 'text-red-600' => $negative, 'text-gray-900' => ! $negative])>{{ $value }}</p>
    <div class="mt-1.5 flex flex-wrap items-center gap-x-2 text-xs">
        @if ($change !== null)
            <span @class(['inline-flex items-center gap-0.5 font-semibold', 'text-emerald-700' => $good, 'text-red-600' => ! $good])>
                {{ $change >= 0 ? '▲' : '▼' }} %{{ number_format(abs($change), 1, ',', '.') }}
            </span>
            <span class="text-gray-500">{{ $compare ? $compare.' ile kıyasla' : 'önceki döneme göre' }}</span>
        @else
            <span class="text-gray-400">{{ $compare ? $compare.' verisi yok' : 'Karşılaştırma verisi yok' }}</span>
        @endif
    </div>
    @if ($hint)
        <p class="mt-2 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
