<x-app-layout>
    <x-slot name="title">Yeni Randevu</x-slot>
    <x-slot name="header">
        <x-page-title title="Yeni randevu" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('appointments.store') }}">
            @include('appointments._form')
        </form>
    </x-card>
</x-app-layout>
