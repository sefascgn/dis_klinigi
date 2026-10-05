{{-- Liste görünümü: tüm tarihler, en yeniden eskiye, sayfalı. --}}
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Hasta</th>
                <th class="hidden md:table-cell">Doktor</th>
                <th>Durum</th>
                <th class="hidden lg:table-cell">Not</th>
                <th class="text-right">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appointments as $appointment)
                <tr>
                    <td class="whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $appointment->starts_at->format('d.m.Y H:i') }}</span>
                        <span class="block text-xs text-gray-500">{{ $appointment->durationInMinutes() }} dk</span>
                    </td>
                    <td>
                        <a href="{{ route('patients.show', $appointment->patient_id) }}" class="link">{{ $appointment->patient->full_name }}</a>
                        <span class="block text-xs text-gray-500">{{ $appointment->patient->phone }}</span>
                    </td>
                    <td class="hidden md:table-cell">{{ $appointment->doctor->full_name }}</td>
                    <td>
                        <x-status-badge :status="$appointment->status" />
                        @if ($appointment->cancellation_reason)
                            <span class="block max-w-[12rem] truncate text-xs text-gray-500" title="{{ $appointment->cancellation_reason }}">{{ $appointment->cancellation_reason }}</span>
                        @endif
                    </td>
                    <td class="hidden max-w-xs truncate lg:table-cell" title="{{ $appointment->notes }}">{{ $appointment->notes ?? '—' }}</td>
                    <td class="text-right"><x-appointment-actions :appointment="$appointment" /></td>
                </tr>
            @empty
                <x-empty-row colspan="6" message="Bu kriterlere uygun randevu yok." />
            @endforelse
        </tbody>
    </table>
</div>

@if ($appointments->hasPages())
    <div class="border-t border-gray-100 px-4 py-3">{{ $appointments->links() }}</div>
@endif
