<x-app-layout>
    <x-slot name="title">Tedavi Ekle</x-slot>
    <x-slot name="header">
        <x-page-title title="Tedavi ekle" :subtitle="$treatment->appointment_id ? 'Randevuya bağlı tedavi kaydı' : null" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('treatments.store') }}">
            @include('treatments._form')
        </form>
    </x-card>
</x-app-layout>
