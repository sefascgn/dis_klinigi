{{-- Hafta görünümü: pazartesiden pazara yedi sütun, randevular saat sırasıyla. --}}
@php
    $byDay = $appointments->groupBy(fn ($a) => $a->starts_at->toDateString());
    $canCreate = auth()->user()->can('create', App\Models\Appointment::class);
    $showDoctor = ! auth()->user()->isDoctor();
@endphp

<div class="overflow-x-auto">
    <div class="grid min-w-[980px] grid-cols-7 divide-x divide-gray-200">
        @for ($day = $from->copy(); $day->lte($to); $day->addDay())
            @php $items = $byDay->get($day->toDateString(), collect()); @endphp
            <div @class(['flex min-h-[420px] flex-col', 'bg-primary-50/40' => $day->isToday(), 'bg-gray-50/60' => $day->isWeekend() && ! $day->isToday()])>
                <div class="flex items-center justify-between gap-1 border-b border-gray-200 px-2 py-2">
                    <a href="{{ $url(['view' => 'day', 'date' => $day->toDateString()]) }}" class="group flex items-center gap-2" title="Gün görünümünde aç">
                        <span @class([
                            'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                            'bg-primary-600 text-white' => $day->isToday(),
                            'text-gray-800 group-hover:bg-gray-100' => ! $day->isToday(),
                        ])>{{ $day->day }}</span>
                        <span class="text-xs font-medium uppercase text-gray-500">{{ $day->translatedFormat('D') }}</span>
                    </a>
                    <div class="flex items-center gap-1">
                        @if ($items->isNotEmpty())
                            <span class="rounded-full bg-gray-100 px-1.5 text-[11px] font-medium text-gray-600">{{ $items->count() }}</span>
                        @endif
                        @if ($canCreate && ! $day->copy()->endOfDay()->isPast())
                            <a href="{{ route('appointments.create', ['date' => $day->toDateString()]) }}" class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-primary-700" title="Bu güne randevu ekle">
                                <x-icon name="plus" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>

                <div class="flex-1 space-y-1.5 p-1.5">
                    @forelse ($items as $appointment)
                        <x-calendar-event :appointment="$appointment" :show-doctor="$showDoctor" />
                    @empty
                        <p class="pt-4 text-center text-xs text-gray-300">—</p>
                    @endforelse
                </div>
            </div>
        @endfor
    </div>
</div>
