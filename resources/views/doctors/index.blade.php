<x-app-layout>
    <x-slot name="title">Doktorlar</x-slot>
    <x-slot name="header">
        <x-page-title title="Doktorlar" :subtitle="$doctors->total().' kayıt'">
            @can('create', App\Models\Doctor::class)
                <a href="{{ route('doctors.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Yeni doktor</a>
            @endcan
        </x-page-title>
    </x-slot>

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4">
            <x-search-form placeholder="Ad, uzmanlık, telefon" />
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Doktor</th>
                        <th>Uzmanlık</th>
                        <th class="hidden md:table-cell">İletişim</th>
                        <th class="hidden lg:table-cell">Panel hesabı</th>
                        <th>Yaklaşan</th>
                        <th>Durum</th>
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($doctors as $doctor)
                        <tr>
                            <td>
                                @can('view', $doctor)
                                    <a href="{{ route('doctors.show', $doctor) }}" class="link">{{ $doctor->full_name }}</a>
                                @else
                                    {{ $doctor->full_name }}
                                @endcan
                            </td>
                            <td>{{ $doctor->specialty ?? '—' }}</td>
                            <td class="hidden md:table-cell">
                                <p>{{ \App\Support\Phone::format($doctor->phone) ?? '—' }}</p>
                                @if ($doctor->email)<p class="text-xs text-gray-500">{{ $doctor->email }}</p>@endif
                            </td>
                            <td class="hidden lg:table-cell">{{ $doctor->user?->email ?? '—' }}</td>
                            <td>{{ $doctor->upcoming_count }}</td>
                            <td>
                                <x-badge :color="$doctor->is_active ? 'green' : 'gray'">{{ $doctor->is_active ? 'Aktif' : 'Pasif' }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap text-right">
                                @can('update', $doctor)
                                    <x-edit-link :href="route('doctors.edit', $doctor)" />
                                @endcan
                                @can('delete', $doctor)
                                    <x-delete-button :action="route('doctors.destroy', $doctor)" confirm="{{ $doctor->full_name }} silinsin mi?" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <x-empty-row colspan="7" message="Doktor bulunamadı." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($doctors->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $doctors->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
