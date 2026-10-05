{{-- Parametreler: $treatments, $showPatient (bool) --}}
@php $showPrice = auth()->user()->can('viewAny', App\Models\Payment::class) || auth()->user()->isDoctor(); @endphp

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Tarih</th>
                @if ($showPatient)<th>Hasta</th>@endif
                <th>İşlem</th>
                <th>Diş</th>
                <th class="hidden md:table-cell">Doktor</th>
                <th>Durum</th>
                @if ($showPrice)<th class="text-right">Ücret</th>@endif
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($treatments as $treatment)
                <tr>
                    <td class="whitespace-nowrap">{{ $treatment->treatment_date->format('d.m.Y') }}</td>
                    @if ($showPatient)
                        <td><a href="{{ route('patients.show', $treatment->patient_id) }}" class="link">{{ $treatment->patient->full_name }}</a></td>
                    @endif
                    <td>
                        <span class="font-medium text-gray-900">{{ $treatment->treatmentType->name }}</span>
                        @if ($treatment->notes)<p class="max-w-xs truncate text-xs text-gray-500" title="{{ $treatment->notes }}">{{ $treatment->notes }}</p>@endif
                    </td>
                    <td>{{ $treatment->tooth_number ?? '—' }}</td>
                    <td class="hidden md:table-cell">{{ $treatment->doctor->full_name }}</td>
                    <td><x-status-badge :status="$treatment->status" /></td>
                    @if ($showPrice)
                        <td @class(['whitespace-nowrap text-right', 'text-gray-400 line-through' => $treatment->status === App\Enums\TreatmentStatus::Cancelled])>@money($treatment->price)</td>
                    @endif
                    <td class="whitespace-nowrap text-right">
                        @can('update', $treatment)
                            <x-edit-link :href="route('treatments.edit', $treatment)" />
                        @endcan
                        @can('delete', $treatment)
                            <x-delete-button :action="route('treatments.destroy', $treatment)" confirm="Tedavi kaydı silinsin mi?" />
                        @endcan
                    </td>
                </tr>
            @empty
                <x-empty-row :colspan="6 + (int) $showPatient + (int) $showPrice" message="Tedavi kaydı yok." />
            @endforelse
        </tbody>
    </table>
</div>
