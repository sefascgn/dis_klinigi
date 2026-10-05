@props(['href', 'label' => 'Düzenle'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-sm text-gray-600 hover:bg-gray-100 focus:ring-primary-500']) }} title="{{ $label }}">
    <x-icon name="pencil" class="h-4 w-4" />
    <span class="sr-only sm:not-sr-only">{{ $label }}</span>
</a>
