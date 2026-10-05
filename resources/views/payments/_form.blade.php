@csrf

<div class="space-y-5">
    <x-patient-picker :patient="$patient" />

    @if ($patient && isset($patient->treatments_total))
        <div class="grid grid-cols-3 gap-2 rounded-lg bg-gray-50 p-3 text-center text-sm">
            <div><p class="text-xs text-gray-500">Tedavi toplamı</p><p class="font-semibold">@money($patient->totalTreatmentAmount())</p></div>
            <div><p class="text-xs text-gray-500">Ödenen</p><p class="font-semibold text-emerald-700">@money($patient->totalPaid())</p></div>
            <div><p class="text-xs text-gray-500">Kalan</p><p @class(['font-semibold', 'text-red-600' => $patient->balance() > 0])>@money($patient->balance())</p></div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="amount" label="Tutar (₺)" type="number" step="0.01" min="0.01" required :value="$payment->amount" />
        <x-form.input name="paid_at" label="Ödeme tarihi" type="date" required :value="$payment->paid_at?->toDateString()" :max="today()->toDateString()" />
        <x-form.select name="method" label="Ödeme yöntemi" required :options="App\Enums\PaymentMethod::options()" :value="$payment->method" class="sm:col-span-2" />
    </div>

    <x-form.input name="notes" label="Not" :value="$payment->notes" maxlength="255" placeholder="Örn. 2. taksit" />

    <x-form.actions :cancel="$patient ? route('patients.show', $patient) : route('payments.index')" />
</div>
