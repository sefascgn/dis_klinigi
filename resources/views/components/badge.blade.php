@props(['color' => 'gray'])

@php
    // Tailwind dinamik sınıfları derleyemediği için tam sınıf adları burada tutulur.
    $classes = [
        'gray' => 'bg-gray-100 text-gray-700 ring-gray-200',
        'yellow' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'purple' => 'bg-violet-50 text-violet-700 ring-violet-200',
    ][$color] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
