{{-- Panelin sayfalama görünümü (AppServiceProvider'da varsayılan yapılır). --}}
@if ($paginator->hasPages())
    @php
        $base = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium transition';
        $idle = 'border border-gray-200 bg-white text-gray-700 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700';
        $disabled = 'border border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed';
    @endphp

    <nav role="navigation" aria-label="Sayfalama" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-sm text-gray-500">
            @if ($paginator->firstItem())
                <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                arası gösteriliyor, toplam
                <span class="font-medium text-gray-700">{{ $paginator->total() }}</span> kayıt
            @else
                Toplam {{ $paginator->total() }} kayıt
            @endif
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="{{ $base }} {{ $disabled }}" aria-disabled="true" aria-label="Önceki sayfa">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} {{ $idle }}" aria-label="Önceki sayfa">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                </a>
            @endif

            {{-- Mobilde yalnızca "sayfa x / y" gösterilir. --}}
            <span class="px-2 text-sm text-gray-600 sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1 text-sm text-gray-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $base }} bg-primary-600 text-white shadow-sm" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $base }} {{ $idle }}" aria-label="Sayfa {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} {{ $idle }}" aria-label="Sonraki sayfa">
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span class="{{ $base }} {{ $disabled }}" aria-disabled="true" aria-label="Sonraki sayfa">
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
