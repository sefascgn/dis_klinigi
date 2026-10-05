<x-app-layout>
    <x-slot name="title">İşlem Tanımları</x-slot>
    <x-slot name="header">
        <x-page-title title="İşlem tanımları" subtitle="Klinikte uygulanan diş işlemleri ve varsayılan ücretleri">
            @can('create', App\Models\TreatmentType::class)
                <a href="{{ route('treatment-types.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Yeni işlem</a>
            @endcan
        </x-page-title>
    </x-slot>

    <x-card :padding="false">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>İşlem</th>
                        <th class="text-right">Varsayılan ücret</th>
                        <th class="hidden md:table-cell">Kullanım</th>
                        <th>Durum</th>
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($treatmentTypes as $type)
                        <tr>
                            <td>
                                <span class="font-medium text-gray-900">{{ $type->name }}</span>
                                @if ($type->description)<p class="max-w-md truncate text-xs text-gray-500">{{ $type->description }}</p>@endif
                            </td>
                            <td class="whitespace-nowrap text-right">@money($type->default_price)</td>
                            <td class="hidden md:table-cell">{{ $type->treatments_count }} kayıt</td>
                            <td><x-badge :color="$type->is_active ? 'green' : 'gray'">{{ $type->is_active ? 'Aktif' : 'Pasif' }}</x-badge></td>
                            <td class="whitespace-nowrap text-right">
                                <x-edit-link :href="route('treatment-types.edit', $type)" />
                                @if ($type->treatments_count === 0)
                                    <x-delete-button :action="route('treatment-types.destroy', $type)" confirm="{{ $type->name }} silinsin mi?" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row colspan="5" message="Henüz işlem tanımı yok." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($treatmentTypes->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $treatmentTypes->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
