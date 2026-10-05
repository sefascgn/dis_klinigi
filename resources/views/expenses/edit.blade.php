<x-app-layout>
    <x-slot name="title">Gider Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title title="Gider düzenle" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('expenses.update', $expense) }}">
            @method('PUT')
            @include('expenses._form')
        </form>
    </x-card>
</x-app-layout>
