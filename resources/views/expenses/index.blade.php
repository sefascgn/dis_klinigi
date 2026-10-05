<x-app-layout>
    <x-slot name="title">Giderler</x-slot>
    <x-slot name="header">
        <x-page-title title="Giderler" :subtitle="$from->format('d.m.Y').' – '.$to->format('d.m.Y')">
            <a href="{{ route('expenses.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Gider ekle</a>
        </x-page-title>
    </x-slot>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card flex flex-col justify-center p-5">
            <p class="text-sm text-gray-500">Seçili dönem toplam gider</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">@money($total)</p>
            <p class="mt-1 text-xs text-gray-500">{{ $expenses->total() }} kayıt</p>
            <a href="{{ route('reports.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="link mt-3 text-sm">Gelir – gider raporunu gör →</a>
        </div>
        <x-card title="Kategoriye göre" class="lg:col-span-2">
            <x-charts.bar-list :items="$byCategory->map(fn ($r) => ['label' => $r->category->label(), 'value' => (float) $r->total])" color="var(--series-2)" empty="Bu dönemde gider kaydı yok." />
        </x-card>
    </div>

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4">
            <x-search-form placeholder="Açıklamada ara">
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-input sm:w-40" aria-label="Başlangıç">
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-input sm:w-40" aria-label="Bitiş">
                <select name="category" class="form-input sm:w-52" aria-label="Kategori">
                    <option value="">Tüm kategoriler</option>
                    @foreach (App\Enums\ExpenseCategory::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-search-form>
            @error('to')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Tarih</th><th>Açıklama</th><th class="hidden md:table-cell">Kategori</th><th class="hidden lg:table-cell">Yöntem</th><th class="text-right">Tutar</th><th class="text-right">İşlemler</th></tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td class="whitespace-nowrap">{{ $expense->spent_at->format('d.m.Y') }}</td>
                            <td>
                                <span class="font-medium text-gray-900">{{ $expense->description }}</span>
                                <span class="block text-xs text-gray-500 md:hidden">{{ $expense->category->label() }}</span>
                            </td>
                            <td class="hidden md:table-cell">{{ $expense->category->label() }}</td>
                            <td class="hidden lg:table-cell">{{ $expense->method->label() }}</td>
                            <td class="whitespace-nowrap text-right font-medium tabular-nums">@money($expense->amount)</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('expenses.create', ['copy' => $expense->id]) }}" class="btn btn-sm text-gray-600 hover:bg-gray-100 focus:ring-primary-500" title="Bugünün tarihiyle tekrar ekle (ör. aylık kira)">
                                    <x-icon name="plus" class="h-4 w-4" /><span class="sr-only sm:not-sr-only">Tekrarla</span>
                                </a>
                                <x-edit-link :href="route('expenses.edit', $expense)" />
                                <x-delete-button :action="route('expenses.destroy', $expense)" confirm="Gider kaydı silinsin mi?" />
                            </td>
                        </tr>
                    @empty
                        <x-empty-row colspan="6" message="Bu dönemde gider kaydı yok." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($expenses->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $expenses->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
