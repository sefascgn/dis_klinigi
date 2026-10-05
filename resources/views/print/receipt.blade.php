<x-print-layout title="Tahsilat Makbuzu">
    <x-slot name="meta">
        <p class="text-xs text-gray-600">Makbuz no: {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</p>
    </x-slot>

    <div class="mx-auto max-w-md space-y-3 text-sm">
        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Hasta</span><span class="font-semibold">{{ $payment->patient->full_name }}</span></div>
        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Ödeme tarihi</span><span>{{ $payment->paid_at->format('d.m.Y') }}</span></div>
        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Ödeme yöntemi</span><span>{{ $payment->method->label() }}</span></div>
        @if ($payment->notes)
            <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Açıklama</span><span>{{ $payment->notes }}</span></div>
        @endif
        <div class="flex items-center justify-between rounded-lg bg-gray-100 px-4 py-3">
            <span class="font-semibold">Tahsil edilen tutar</span>
            <span class="text-xl font-bold">@money($payment->amount)</span>
        </div>
        <div class="flex justify-between pt-1 text-gray-600"><span>Güncel kalan bakiye</span><span>@money(max($patient->balance(), 0))</span></div>
    </div>

    <x-slot name="footer">
        <div class="grid grid-cols-2 gap-10 text-center text-gray-700">
            <div class="border-t border-gray-400 pt-1">Ödeyen</div>
            <div class="border-t border-gray-400 pt-1">Teslim alan{{ $payment->receiver ? ': '.$payment->receiver->name : '' }}</div>
        </div>
        <p class="mt-6 text-center">Bu belge mali değeri olmayan bilgilendirme amaçlı tahsilat makbuzudur.</p>
    </x-slot>
</x-print-layout>
