<x-app-layout>
    <x-slot name="title">Ödeme Düzenle</x-slot>
    <x-slot name="header">
        <x-page-title title="Ödeme düzenle" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('payments.update', $payment) }}">
            @method('PUT')
            @include('payments._form')
        </form>
    </x-card>
</x-app-layout>
