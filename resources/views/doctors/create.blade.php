<x-app-layout>
    <x-slot name="title">Yeni Doktor</x-slot>
    <x-slot name="header">
        <x-page-title title="Yeni doktor" />
    </x-slot>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('doctors.store') }}">
            @include('doctors._form')
        </form>
    </x-card>
</x-app-layout>
