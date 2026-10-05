<x-app-layout>
    <x-slot name="title">Tedavi Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title title="Tedavi düzenle" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('treatments.update', $treatment) }}">
            @method('PUT')
            @include('treatments._form')
        </form>
    </x-card>
</x-app-layout>
