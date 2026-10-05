{{-- Ay görünümü: klasik takvim ızgarası; her günde ilk 3 randevu, kalanı için gün görünümüne bağlantı. --}}
@php
    use App\Enums\AppointmentStatus;

    $byDay = $appointments->groupBy(fn ($a) => $a->starts_at->toDateString());
    $dotClass = fn ($status) => match ($status) {
        AppointmentStatus::Pending => 'bg-amber-400',
        AppointmentStatus::Confirmed => 'bg-sky-500',
        AppointmentStatus::Completed => 'bg-emerald-500',
        AppointmentStatus::Cancelled => 'bg-gray-300',
    };
    $limit = 3;
@endphp

<div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold uppercase text-gray-500">
    @foreach (['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $weekday)
        <div class="py-2">{{ $weekday }}</div>
    @endforeach
</div>

<div class="grid grid-cols-7">
    @for ($day = $from->copy(); $day->lte($to); $day->addDay())
        @php
            $items = $byDay->get($day->toDateString(), collect());
            $inMonth = $day->isSameMonth($date);
            $dayUrl = $url(['view' => 'day', 'date' => $day->toDateString()]);
        @endphp
        <div @class([
            'min-h-[72px] border-b border-r border-gray-100 p-1 sm:min-h-[118px] sm:p-1.5',
            'bg-gray-50/70' => ! $inMonth,
            'bg-primary-50/40' => $day->isToday(),
        ])>
            <div class="mb-1 flex items-center justify-between">
                <a href="{{ $dayUrl }}" @class([
                    'flex h-7 w-7 items-center justify-center rounded-full text-sm',
                    'bg-primary-600 font-semibold text-white' => $day->isToday(),
                    'font-medium text-gray-800 hover:bg-gray-100' => $inMonth && ! $day->isToday(),
                    'text-gray-400 hover:bg-gray-100' => ! $inMonth,
                ]) title="{{ $day->translatedFormat('j F l') }}">{{ $day->day }}</a>

                {{-- Mobilde sadece sayı gösterilir --}}
                @if ($items->isNotEmpty())
                    <a href="{{ $dayUrl }}" class="rounded-full bg-primary-100 px-1.5 text-[11px] font-semibold text-primary-800 sm:hidden">{{ $items->count() }}</a>
                @endif
            </div>

            <div class="hidden space-y-0.5 sm:block">
                @foreach ($items->take($limit) as $appointment)
                    <a href="{{ $dayUrl }}"
                       title="{{ $appointment->starts_at->format('H:i') }} · {{ $appointment->patient->full_name }} · {{ $appointment->doctor->full_name }} · {{ $appointment->status->label() }}"
                       @class(['flex items-center gap-1 truncate rounded px-1 py-0.5 text-xs hover:bg-gray-100', 'text-gray-400 line-through' => $appointment->status === AppointmentStatus::Cancelled, 'text-gray-700' => $appointment->status !== AppointmentStatus::Cancelled])>
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $dotClass($appointment->status) }}"></span>
                        <span class="font-medium">{{ $appointment->starts_at->format('H:i') }}</span>
                        <span class="truncate">{{ $appointment->patient->full_name }}</span>
                    </a>
                @endforeach
                @if ($items->count() > $limit)
                    <a href="{{ $dayUrl }}" class="block px-1 text-xs font-medium text-primary-700 hover:underline">+{{ $items->count() - $limit }} randevu daha</a>
                @endif
            </div>
        </div>
    @endfor
</div>
