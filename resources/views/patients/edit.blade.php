<x-app-layout>
    <x-slot name="title">Hasta Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title :title="$patient->full_name" subtitle="Hasta bilgilerini düzenle" />
    </x-slot>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('patients.update', $patient) }}">
            @method('PUT')
            @include('patients._form')
        </form>
    </x-card>
</x-app-layout>
