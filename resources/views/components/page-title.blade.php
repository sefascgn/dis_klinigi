@props(['title', 'subtitle' => null])

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0">
        <h1 class="truncate text-xl font-semibold text-gray-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
