<x-app-layout>
    <x-slot name="title">İşlem Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title :title="$treatmentType->name" subtitle="İşlem tanımını düzenle" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('treatment-types.update', $treatmentType) }}">
            @method('PUT')
            @include('treatment-types._form')
        </form>
    </x-card>
</x-app-layout>
