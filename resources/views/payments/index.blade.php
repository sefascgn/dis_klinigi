<x-app-layout>
    <x-slot name="title">Ödemeler</x-slot>
    <x-slot name="header">
        <x-page-title title="Ödemeler" :subtitle="$from->format('d.m.Y').' – '.$to->format('d.m.Y')">
            @can('create', App\Models\Payment::class)
                <a href="{{ route('payments.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Ödeme al</a>
            @endcan
        </x-page-title>
    </x-slot>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card label="Seçili dönem toplamı" :value="App\Support\Money::format($total)" icon="cash" color="purple" />
        <x-stat-card label="Ödeme sayısı" :value="$payments->total()" icon="list" color="blue" />
    </div>

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4">
            <x-search-form placeholder="Hasta adı veya telefon">
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-input sm:w-40" aria-label="Başlangıç">
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-input sm:w-40" aria-label="Bitiş">
                <select name="method" class="form-input sm:w-40" aria-label="Ödeme yöntemi">
                    <option value="">Tüm yöntemler</option>
                    @foreach (App\Enums\PaymentMethod::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-search-form>
            @if ($errors->any())
                <p class="form-error">{{ $errors->first() }}</p>
            @endif
        </div>

        @include('payments._table', ['payments' => $payments, 'showPatient' => true])

        @if ($payments->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $payments->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
