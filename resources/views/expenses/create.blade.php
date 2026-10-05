<x-app-layout>
    <x-slot name="title">Gider Ekle</x-slot>
    <x-slot name="header">
        <x-page-title title="Gider ekle" :subtitle="request('copy') ? 'Seçilen gider bugünün tarihiyle kopyalandı' : null" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('expenses.store') }}">
            @include('expenses._form')
        </form>
    </x-card>
</x-app-layout>
