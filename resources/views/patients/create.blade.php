<x-app-layout>
    <x-slot name="title">Yeni Hasta</x-slot>
    <x-slot name="header">
        <x-page-title title="Yeni hasta" />
    </x-slot>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('patients.store') }}">
            @include('patients._form')
        </form>
    </x-card>
</x-app-layout>
