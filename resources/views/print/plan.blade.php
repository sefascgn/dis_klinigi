<x-print-layout title="Tedavi Planı ve Fiyat Teklifi">
    <x-slot name="meta">
        <p class="text-xs text-gray-600">Geçerlilik: {{ now()->addDays(30)->format('d.m.Y') }}</p>
    </x-slot>

    <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-xs uppercase text-gray-500">Hasta</p>
            <p class="font-semibold">{{ $patient->full_name }}</p>
            <p>{{ $patient->formatted_phone }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase text-gray-500">Hasta no</p>
            <p class="font-semibold">#{{ $patient->id }}</p>
        </div>
    </div>

    <table class="w-full border-collapse text-sm">
        <thead>
            <tr class="border-b border-gray-300 text-left text-xs uppercase text-gray-500">
                <th class="py-2 pr-2">#</th>
                <th class="py-2 pr-2">İşlem</th>
                <th class="py-2 pr-2">Diş</th>
                <th class="py-2 pr-2">Doktor</th>
                <th class="py-2 text-right">Ücret</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($treatments as $treatment)
                <tr class="border-b border-gray-100 align-top">
                    <td class="py-2 pr-2 text-gray-500">{{ $loop->iteration }}</td>
                    <td class="py-2 pr-2">
                        <span class="font-medium">{{ $treatment->treatmentType->name }}</span>
                        @if ($treatment->notes)<p class="text-xs text-gray-500">{{ $treatment->notes }}</p>@endif
                    </td>
                    <td class="py-2 pr-2">{{ $treatment->tooth_number ?? '—' }}</td>
                    <td class="py-2 pr-2">{{ $treatment->doctor->full_name }}</td>
                    <td class="whitespace-nowrap py-2 text-right">@money($treatment->price)</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-gray-500">Planlanmış tedavi bulunmuyor. Hasta kartından “Planlandı” durumunda tedavi ekleyebilirsiniz.</td></tr>
            @endforelse
        </tbody>
        @if ($treatments->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4" class="pt-3 text-right font-semibold">Toplam</td>
                    <td class="whitespace-nowrap pt-3 text-right text-base font-bold">@money($treatments->sum('price'))</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <x-slot name="footer">
        Bu teklif bilgilendirme amaçlıdır; tedavi sürecinde klinik muayene bulgularına göre değişiklik gösterebilir.
        <div class="mt-10 grid grid-cols-2 gap-10 text-center text-gray-700">
            <div class="border-t border-gray-400 pt-1">Hasta / Veli imzası</div>
            <div class="border-t border-gray-400 pt-1">Hekim imzası</div>
        </div>
    </x-slot>
</x-print-layout>
