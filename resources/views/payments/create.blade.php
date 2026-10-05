<x-app-layout>
    <x-slot name="title">Ödeme Al</x-slot>
    <x-slot name="header">
        <x-page-title title="Ödeme al" />
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('payments.store') }}">
            @include('payments._form')
        </form>
    </x-card>
</x-app-layout>
