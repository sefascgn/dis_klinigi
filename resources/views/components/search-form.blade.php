{{-- GET filtre formu: arama kutusu + isteğe bağlı ek filtreler (slot). --}}
@props(['placeholder' => 'Ara...', 'action' => url()->current()])

<form method="GET" action="{{ $action }}" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
    <div class="relative sm:w-72">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="form-input pl-9">
    </div>
    {{ $slot }}
    <div class="flex gap-2">
        <button type="submit" class="btn-secondary">Filtrele</button>
        @if (collect(request()->query())->except('page')->filter(fn ($v) => filled($v))->isNotEmpty())
            <a href="{{ $action }}" class="btn-ghost">Temizle</a>
        @endif
    </div>
</form>
