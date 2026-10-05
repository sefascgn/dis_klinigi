<x-app-layout>
    <x-slot name="title">Hastalar</x-slot>
    <x-slot name="header">
        <x-page-title title="Hastalar" :subtitle="$patients->total().' kayıt'">
            @can('create', App\Models\Patient::class)
                <a href="{{ route('patients.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Yeni hasta</a>
            @endcan
        </x-page-title>
    </x-slot>

    @php $showBalance = auth()->user()->can('viewAny', App\Models\Payment::class); @endphp

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4">
            <x-search-form placeholder="Ad, telefon, T.C. no, e-posta" />
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Hasta</th>
                        <th>Telefon</th>
                        <th class="hidden md:table-cell">T.C. Kimlik No</th>
                        <th class="hidden lg:table-cell">Yaş</th>
                        @if ($showBalance)
                            <th class="text-right">Bakiye</th>
                        @endif
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $patient)
                        <tr>
                            <td>
                                <a href="{{ route('patients.show', $patient) }}" class="link">{{ $patient->full_name }}</a>
                                @if ($patient->allergies)
                                    <x-badge color="red" class="ml-1" title="{{ $patient->allergies }}">Alerji</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $patient->phone }}</td>
                            <td class="hidden md:table-cell">{{ $patient->national_id ?? '—' }}</td>
                            <td class="hidden lg:table-cell">{{ $patient->age ?? '—' }}</td>
                            @if ($showBalance)
                                <td @class(['whitespace-nowrap text-right font-medium', 'text-red-600' => $patient->balance() > 0, 'text-gray-500' => $patient->balance() <= 0])>
                                    @money($patient->balance())
                                </td>
                            @endif
                            <td class="whitespace-nowrap text-right">
                                @can('update', $patient)
                                    <x-edit-link :href="route('patients.edit', $patient)" />
                                @endcan
                                @can('delete', $patient)
                                    <x-delete-button :action="route('patients.destroy', $patient)" confirm="{{ $patient->full_name }} kaydı silinsin mi? Geçmiş kayıtlar arşivde korunur." />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="$showBalance ? 6 : 5" message="Hasta bulunamadı." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($patients->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $patients->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
