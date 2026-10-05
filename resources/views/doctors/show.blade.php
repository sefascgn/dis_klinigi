<x-app-layout>
    <x-slot name="title">{{ $doctor->full_name }}</x-slot>
    <x-slot name="header">
        <x-page-title :title="$doctor->full_name" :subtitle="$doctor->specialty">
            @can('create', App\Models\Appointment::class)
                @if ($doctor->is_active)
                    <a href="{{ route('appointments.create', ['doctor_id' => $doctor->id]) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Randevu oluştur</a>
                @endif
            @endcan
            @can('update', $doctor)
                <a href="{{ route('doctors.edit', $doctor) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" />Düzenle</a>
            @endcan
        </x-page-title>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6">
            <x-card title="Doktor bilgileri">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Durum</dt><dd><x-badge :color="$doctor->is_active ? 'green' : 'gray'">{{ $doctor->is_active ? 'Aktif' : 'Pasif' }}</x-badge></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Uzmanlık</dt><dd class="text-right">{{ $doctor->specialty ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Telefon</dt><dd class="text-right">{{ $doctor->phone ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">E-posta</dt><dd class="truncate text-right">{{ $doctor->email ?? '—' }}</dd></div>
                </dl>
            </x-card>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:grid-cols-1">
                <x-stat-card label="Yaklaşan randevu" :value="$stats['upcoming']" icon="calendar" />
                <x-stat-card label="Tamamlanan randevu" :value="$stats['completed']" icon="check" color="green" />
                <x-stat-card label="Uygulanan tedavi" :value="$stats['treatments']" icon="tooth" color="blue" />
            </div>
        </div>

        <x-card title="Randevular" :padding="false" class="xl:col-span-2">
            <x-slot name="actions">
                <div class="flex rounded-lg border border-gray-200 p-0.5 text-sm">
                    @foreach (['upcoming' => 'Bugün ve sonrası', 'past' => 'Geçmiş'] as $key => $label)
                        <a href="{{ route('doctors.show', [$doctor, 'period' => $key]) }}"
                           @class(['rounded-md px-3 py-1', 'bg-primary-600 text-white' => $period === $key, 'text-gray-600 hover:bg-gray-100' => $period !== $key])>{{ $label }}</a>
                    @endforeach
                </div>
            </x-slot>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tarih</th><th>Hasta</th><th>Durum</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            <tr>
                                <td class="whitespace-nowrap">{{ $appointment->starts_at->translatedFormat('d.m.Y D H:i') }}</td>
                                <td><a href="{{ route('patients.show', $appointment->patient_id) }}" class="link">{{ $appointment->patient->full_name }}</a></td>
                                <td><x-status-badge :status="$appointment->status" /></td>
                                <td class="text-right">
                                    @can('view', $appointment)
                                        <x-appointment-actions :appointment="$appointment" />
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <x-empty-row colspan="4" message="Randevu yok." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($appointments->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $appointments->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
