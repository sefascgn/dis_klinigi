{{-- Hastanın diş bazlı tedavilerini FDI şemasında gösterir. --}}
@props(['treatments'])

@php
    use App\Enums\TreatmentStatus;
    use App\Support\Teeth;

    $byTooth = $treatments
        ->filter(fn ($t) => $t->tooth_number && $t->status !== TreatmentStatus::Cancelled)
        ->groupBy('tooth_number');

    $rows = Teeth::PERMANENT;
    if ($byTooth->keys()->contains(fn ($n) => $n >= 51)) {
        $rows += ['deciduous_upper' => Teeth::DECIDUOUS['upper'], 'deciduous_lower' => Teeth::DECIDUOUS['lower']];
    }

    $toothClass = function (int $number) use ($byTooth) {
        $items = $byTooth->get($number);
        if (! $items) {
            return 'border-gray-200 bg-white text-gray-400';
        }

        return $items->contains(fn ($t) => $t->status === TreatmentStatus::Completed)
            ? 'border-primary-400 bg-primary-500 text-white'
            : 'border-amber-300 bg-amber-100 text-amber-800';
    };

    $tooltip = fn (int $number) => $byTooth->get($number)
        ?->map(fn ($t) => $t->treatmentType->name.' ('.$t->status->label().', '.$t->treatment_date->format('d.m.Y').')')
        ->implode("\n");
@endphp

<div class="overflow-x-auto">
    <div class="mx-auto w-max space-y-2 py-2">
        @foreach ($rows as $key => $teeth)
            @if ($key === 'lower' || $key === 'deciduous_upper')
                <div class="border-t border-dashed border-gray-300"></div>
            @endif
            <div class="flex justify-center gap-1">
                @foreach ($teeth as $i => $number)
                    @if ($i === intdiv(count($teeth), 2))
                        <span class="w-2"></span>
                    @endif
                    <span title="{{ $tooltip($number) ?? 'Diş '.$number }}"
                          class="flex h-9 w-8 cursor-default items-center justify-center rounded-md border text-xs font-semibold {{ $toothClass($number) }}">
                        {{ $number }}
                    </span>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<div class="mt-3 flex flex-wrap justify-center gap-4 text-xs text-gray-500">
    <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-primary-500"></span>Tedavi tamamlandı</span>
    <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-amber-300 bg-amber-100"></span>Planlandı / devam ediyor</span>
    <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-gray-200 bg-white"></span>İşlem yok</span>
</div>
