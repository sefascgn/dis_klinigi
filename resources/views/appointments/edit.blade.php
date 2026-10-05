<x-app-layout>
    <x-slot name="title">Randevu Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title title="Randevu düzenle" :subtitle="$appointment->starts_at->translatedFormat('j F Y, H:i')" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('appointments.update', $appointment) }}">
            @method('PUT')
            @include('appointments._form')
        </form>
    </x-card>
</x-app-layout>
