@props(['action', 'confirm' => 'Bu kaydı silmek istediğinize emin misiniz?', 'label' => 'Sil'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-sm text-red-600 hover:bg-red-50 focus:ring-red-500']) }} title="{{ $label }}">
        <x-icon name="trash" class="h-4 w-4" />
        <span class="sr-only sm:not-sr-only">{{ $label }}</span>
    </button>
</form>
