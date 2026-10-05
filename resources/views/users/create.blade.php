<x-app-layout>
    <x-slot name="title">Yeni Kullanıcı</x-slot>
    <x-slot name="header">
        <x-page-title title="Yeni kullanıcı" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('users.store') }}">
            @include('users._form')
        </form>
    </x-card>
</x-app-layout>
