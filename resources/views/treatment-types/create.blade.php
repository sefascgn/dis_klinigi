<x-app-layout>
    <x-slot name="title">Yeni İşlem</x-slot>
    <x-slot name="header">
        <x-page-title title="Yeni işlem tanımı" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('treatment-types.store') }}">
            @include('treatment-types._form')
        </form>
    </x-card>
</x-app-layout>
