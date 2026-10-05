<x-app-layout>
    <x-slot name="title">Kullanıcı Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title :title="$user->name" subtitle="Kullanıcı hesabını düzenle" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @method('PUT')
            @include('users._form')
        </form>
    </x-card>
</x-app-layout>
