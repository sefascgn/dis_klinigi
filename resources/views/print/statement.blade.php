<x-print-layout title="Hesap Ekstresi">
    <div class="mb-6">
        <p class="text-xs uppercase text-gray-500">Hasta</p>
        <p class="font-semibold">{{ $patient->full_name }} <span class="font-normal text-gray-500">(#{{ $patient->id }})</span></p>
        <p>{{ $patient->phone }}</p>
    </div>

    @php $balance = 0; @endphp
    <table class="w-full border-collapse text-sm">
        <thead>
            <tr class="border-b border-gray-300 text-left text-xs uppercase text-gray-500">
                <th class="py-2 pr-2">Tarih</th>
                <th class="py-2 pr-2">Açıklama</th>
                <th class="py-2 pr-2 text-right">Borç</th>
                <th class="py-2 pr-2 text-right">Ödeme</th>
                <th class="py-2 text-right">Bakiye</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php $balance += $row['debit'] - $row['credit']; @endphp
                <tr class="border-b border-gray-100">
                    <td class="whitespace-nowrap py-2 pr-2">{{ $row['date']->format('d.m.Y') }}</td>
                    <td class="py-2 pr-2">{{ $row['description'] }}</td>
                    <td class="whitespace-nowrap py-2 pr-2 text-right">{{ $row['debit'] ? App\Support\Money::format($row['debit']) : '' }}</td>
                    <td class="whitespace-nowrap py-2 pr-2 text-right">{{ $row['credit'] ? App\Support\Money::format($row['credit']) : '' }}</td>
                    <td class="whitespace-nowrap py-2 text-right">@money($balance)</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-gray-500">Hareket bulunmuyor.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-gray-800">
                <td colspan="2" class="pt-3 font-semibold">Toplam</td>
                <td class="whitespace-nowrap pt-3 text-right font-semibold">@money($rows->sum('debit'))</td>
                <td class="whitespace-nowrap pt-3 text-right font-semibold">@money($rows->sum('credit'))</td>
                <td class="whitespace-nowrap pt-3 text-right text-base font-bold">@money($balance)</td>
            </tr>
        </tfoot>
    </table>

    <p class="mt-4 text-right text-sm">
        {{ $balance > 0 ? 'Kalan borç' : ($balance < 0 ? 'Hasta alacağı' : 'Hesap kapalı') }}:
        <strong>@money(abs($balance))</strong>
    </p>
</x-print-layout>
