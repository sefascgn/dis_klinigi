<x-app-layout>
    @php
        $filters = request()->only(['doctor_id', 'status', 'q']);
        $url = fn (array $params) => route('appointments.index', [...$filters, ...$params]);

        $title = match ($view) {
            'day' => $date->translatedFormat('j F Y, l'),
            'week' => $from->isSameMonth($to)
                ? $from->translatedFormat('j').' – '.$to->translatedFormat('j F Y')
                : $from->translatedFormat('j F').' – '.$to->translatedFormat('j F Y'),
            'month' => $date->translatedFormat('F Y'),
            default => 'Tüm randevular',
        };

        [$prev, $next] = match ($view) {
            'day' => [$date->copy()->subDay(), $date->copy()->addDay()],
            'week' => [$date->copy()->subWeek(), $date->copy()->addWeek()],
            'month' => [$date->copy()->startOfMonth()->subMonth(), $date->copy()->startOfMonth()->addMonth()],
            default => [null, null],
        };

        $views = [
            'day' => 'Bugün',
            'week' => 'Bu hafta',
            'month' => 'Bu ay',
            'list' => 'Liste',
        ];
    @endphp

    <x-slot name="title">Randevular</x-slot>
    <x-slot name="header">
        <x-page-title title="Randevular" :subtitle="$title">
            @can('create', App\Models\Appointment::class)
                <a href="{{ route('appointments.create', ['date' => $view === 'list' ? null : $date->toDateString()]) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Yeni randevu</a>
            @endcan
        </x-page-title>
    </x-slot>

    <x-card :padding="false">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4">
            {{-- Görünüm seçimi ve gezinme --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                    {{-- Butonlar bugüne gider; görünüm seçiliyse ama başka bir tarihe bakılıyorsa sadece çerçeveli görünür. --}}
                    @foreach ($views as $key => $label)
                        @php
                            $active = $view === $key;
                            $current = $active && ($key === 'list' || today()->between($from->copy()->startOfDay(), $to));
                        @endphp
                        <a href="{{ $url(['view' => $key]) }}"
                           @class([
                               'rounded-md px-3 py-1.5 text-sm font-medium transition',
                               'bg-primary-600 text-white shadow-sm' => $current,
                               'bg-white text-primary-700 ring-1 ring-primary-300' => $active && ! $current,
                               'text-gray-600 hover:bg-white' => ! $active,
                           ])>{{ $label }}</a>
                    @endforeach
                </div>

                @if ($prev)
                    <div class="flex items-center gap-1">
                        <a href="{{ $url(['view' => $view, 'date' => $prev->toDateString()]) }}" class="btn-secondary btn-sm" aria-label="Önceki"><x-icon name="chevron-left" class="h-4 w-4" /></a>
                        <a href="{{ $url(['view' => $view, 'date' => $next->toDateString()]) }}" class="btn-secondary btn-sm" aria-label="Sonraki"><x-icon name="chevron-right" class="h-4 w-4" /></a>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">{{ $title }}</span>
                @endif
            </div>

            <x-search-form placeholder="Hasta adı veya telefon" :action="route('appointments.index', ['view' => $view])">
                <input type="hidden" name="view" value="{{ $view }}">
                @if ($view !== 'list')
                    <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-input sm:w-44" aria-label="Tarihe git">
                @endif
                @unless (auth()->user()->isDoctor())
                    <select name="doctor_id" class="form-input sm:w-52" aria-label="Doktor">
                        <option value="">Tüm doktorlar</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected(request('doctor_id') == $doctor->id)>{{ $doctor->full_name }}</option>
                        @endforeach
                    </select>
                @endunless
                <select name="status" class="form-input sm:w-40" aria-label="Durum">
                    <option value="">Tüm durumlar</option>
                    @foreach (App\Enums\AppointmentStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-search-form>

            @if ($view !== 'list')
                <div class="flex flex-wrap gap-3 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-400"></span>Bekliyor</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-sky-500"></span>Onaylandı</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Tamamlandı</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-gray-300"></span>İptal edildi</span>
                    <span class="ml-auto">{{ $appointments->count() }} randevu</span>
                </div>
            @endif
        </div>

        @include('appointments.views.'.$view)
    </x-card>
</x-app-layout>
