@foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-red-200 bg-red-50 text-red-800'] as $type => $classes)
    @if (session($type))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="mb-4 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="alert">
            <div class="flex items-start gap-2">
                <x-icon :name="$type === 'success' ? 'check' : 'warning'" />
                <span>{{ session($type) }}</span>
            </div>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Kapat">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif
@endforeach
