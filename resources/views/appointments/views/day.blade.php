{{-- Gün görünümü: her doktor bir sütun, dikey eksen saat. --}}
@php
    use App\Enums\AppointmentStatus;

    $hourHeight = 96; // 30 dk'lık kartta saat + hasta adı sığsın
    $pxPerMinute = $hourHeight / 60;
    $gridStart = $startHour * 60;
    $gridHeight = ($endHour - $startHour) * $hourHeight;

    // İptal edilenler aynı saate yeni randevu alınabildiği için ızgarada üst üste binmesin diye ayrı listelenir.
    $cancelledFilter = request('status') === AppointmentStatus::Cancelled->value;
    $gridItems = $cancelledFilter ? $appointments : $appointments->reject(fn ($a) => $a->status === AppointmentStatus::Cancelled);
    $cancelled = $cancelledFilter ? collect() : $appointments->filter(fn ($a) => $a->status === AppointmentStatus::Cancelled);

    $canCreate = auth()->user()->can('create', App\Models\Appointment::class);
    $minutesOf = fn ($time) => $time->isSameDay($date) ? $time->hour * 60 + $time->minute : 24 * 60;
    $nowTop = $date->isToday() ? ($minutesOf(now()) - $gridStart) * $pxPerMinute : null;
@endphp

@if ($columns->isEmpty())
    <p class="px-5 py-10 text-center text-sm text-gray-500">Gösterilecek doktor yok.</p>
@else
    <div class="overflow-x-auto">
        <div style="min-width: {{ 64 + $columns->count() * 190 }}px">
            {{-- Doktor başlıkları --}}
            <div class="flex border-b border-gray-200 bg-gray-50">
                <div class="w-16 shrink-0"></div>
                @foreach ($columns as $doctor)
                    <div class="min-w-0 flex-1 border-l border-gray-200 px-2 py-2 text-center">
                        <a href="{{ route('doctors.show', $doctor) }}" class="block truncate text-sm font-semibold text-gray-800 hover:text-primary-700">{{ $doctor->full_name }}</a>
                        <p class="truncate text-xs text-gray-500">
                            {{ $gridItems->where('doctor_id', $doctor->id)->count() }} randevu{{ $doctor->specialty ? ' · '.$doctor->specialty : '' }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Saat ızgarası --}}
            <div class="flex">
                <div class="relative w-16 shrink-0" style="height: {{ $gridHeight }}px">
                    @for ($h = $startHour; $h < $endHour; $h++)
                        <span class="absolute right-2 text-[11px] text-gray-400" style="top: {{ ($h - $startHour) * $hourHeight + 2 }}px">{{ sprintf('%02d:00', $h) }}</span>
                    @endfor
                </div>

                @foreach ($columns as $doctor)
                    @php $bookable = $canCreate && $doctor->is_active && ! $doctor->trashed(); @endphp
                    <div class="relative min-w-0 flex-1 border-l border-gray-200" style="height: {{ $gridHeight }}px">
                        {{-- Yarım saatlik satırlar; boş alana tıklayınca o saate randevu açılır --}}
                        @for ($m = $gridStart; $m < $endHour * 60; $m += 30)
                            @php $slot = $date->copy()->startOfDay()->addMinutes($m); @endphp
                            @if ($bookable && $slot->copy()->addMinutes(30)->isFuture())
                                <a href="{{ route('appointments.create', ['doctor_id' => $doctor->id, 'date' => $date->toDateString(), 'time' => $slot->format('H:i')]) }}"
                                   title="{{ $slot->format('H:i') }} için randevu oluştur"
                                   @class(['absolute inset-x-0 block hover:bg-primary-50', 'border-t border-gray-100' => $m % 60 === 0, 'border-t border-dashed border-gray-100' => $m % 60 !== 0])
                                   style="top: {{ ($m - $gridStart) * $pxPerMinute }}px; height: {{ 30 * $pxPerMinute }}px"></a>
                            @else
                                <div @class(['absolute inset-x-0', 'border-t border-gray-100' => $m % 60 === 0, 'border-t border-dashed border-gray-100' => $m % 60 !== 0, 'bg-gray-50/60' => ! $bookable])
                                     style="top: {{ ($m - $gridStart) * $pxPerMinute }}px; height: {{ 30 * $pxPerMinute }}px"></div>
                            @endif
                        @endfor

                        @foreach ($gridItems->where('doctor_id', $doctor->id) as $appointment)
                            @php
                                $top = ($minutesOf($appointment->starts_at) - $gridStart) * $pxPerMinute;
                                $height = max(min($minutesOf($appointment->ends_at), $endHour * 60) - $minutesOf($appointment->starts_at), 20) * $pxPerMinute;
                            @endphp
                            <x-calendar-event :appointment="$appointment" :show-doctor="false"
                                              class="absolute inset-x-1 z-10"
                                              style="top: {{ $top + 1 }}px; height: {{ $height - 2 }}px" />
                        @endforeach

                        @if ($nowTop !== null && $nowTop >= 0 && $nowTop <= $gridHeight)
                            <div class="pointer-events-none absolute inset-x-0 z-20 border-t-2 border-red-500" style="top: {{ $nowTop }}px">
                                <span class="absolute -left-1 -top-[5px] h-2 w-2 rounded-full bg-red-500"></span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($cancelled->isNotEmpty())
        <div class="border-t border-gray-100 px-5 py-4">
            <p class="mb-2 text-sm font-semibold text-gray-700">İptal edilen randevular ({{ $cancelled->count() }})</p>
            <ul class="space-y-1 text-sm text-gray-500">
                @foreach ($cancelled as $appointment)
                    <li class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ $appointment->starts_at->format('H:i') }}</span>
                        <a href="{{ route('patients.show', $appointment->patient_id) }}" class="link">{{ $appointment->patient->full_name }}</a>
                        <span>· {{ $appointment->doctor->full_name }}</span>
                        @if ($appointment->cancellation_reason)<span class="text-xs">({{ $appointment->cancellation_reason }})</span>@endif
                        <x-appointment-actions :appointment="$appointment" compact />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
