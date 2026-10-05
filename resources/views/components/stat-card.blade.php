@props(['label', 'value', 'icon', 'color' => 'primary', 'href' => null])

@php
    $iconClasses = [
        'primary' => 'bg-primary-50 text-primary-600',
        'yellow' => 'bg-amber-50 text-amber-600',
        'green' => 'bg-emerald-50 text-emerald-600',
        'blue' => 'bg-sky-50 text-sky-600',
        'purple' => 'bg-violet-50 text-violet-600',
    ][$color] ?? 'bg-gray-100 text-gray-600';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card flex items-center gap-4 p-5'.($href ? ' transition hover:border-primary-300 hover:shadow' : '')]) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $iconClasses }}">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <div class="min-w-0">
        <p class="truncate text-sm text-gray-500">{{ $label }}</p>
        <p class="text-2xl font-semibold text-gray-900">{{ $value }}</p>
    </div>
</{{ $tag }}>
