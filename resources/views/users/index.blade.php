<x-app-layout>
    <x-slot name="title">Kullanıcılar</x-slot>
    <x-slot name="header">
        <x-page-title title="Kullanıcılar" subtitle="Panel hesapları ve rolleri">
            <a href="{{ route('users.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Yeni kullanıcı</a>
        </x-page-title>
    </x-slot>

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4">
            <x-search-form placeholder="Ad veya e-posta">
                <select name="role" class="form-input sm:w-40" aria-label="Rol">
                    <option value="">Tüm roller</option>
                    @foreach (App\Enums\UserRole::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-search-form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Rol</th>
                        <th class="hidden md:table-cell">Bağlı doktor kaydı</th>
                        <th>Durum</th>
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                @if (auth()->user()->is($user))<x-badge class="ml-1">Siz</x-badge>@endif
                                <span class="block text-xs text-gray-500">{{ $user->email }}</span>
                            </td>
                            <td><x-badge :color="$user->role->color()">{{ $user->role->label() }}</x-badge></td>
                            <td class="hidden md:table-cell">
                                @if ($user->doctor)
                                    <a href="{{ route('doctors.show', $user->doctor) }}" class="link">{{ $user->doctor->full_name }}</a>
                                @elseif ($user->isDoctor())
                                    <span class="text-xs text-amber-700">Bağlanmamış</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td><x-badge :color="$user->is_active ? 'green' : 'gray'">{{ $user->is_active ? 'Aktif' : 'Pasif' }}</x-badge></td>
                            <td class="whitespace-nowrap text-right">
                                <x-edit-link :href="route('users.edit', $user)" />
                                @can('delete', $user)
                                    <x-delete-button :action="route('users.destroy', $user)" confirm="{{ $user->name }} hesabı silinsin mi?" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <x-empty-row colspan="5" message="Kullanıcı bulunamadı." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
