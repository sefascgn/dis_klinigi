<x-app-layout>
    <x-slot name="title">Doktor Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title :title="$doctor->full_name" subtitle="Doktor bilgilerini düzenle" />
    </x-slot>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('doctors.update', $doctor) }}">
            @method('PUT')
            @include('doctors._form')
        </form>
    </x-card>
</x-app-layout>
