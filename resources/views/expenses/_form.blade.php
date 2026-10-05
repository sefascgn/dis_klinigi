@csrf

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.select name="category" label="Kategori" required placeholder="Kategori seçin" :options="App\Enums\ExpenseCategory::options()" :value="$expense->category" />
        <x-form.input name="amount" label="Tutar (₺)" type="number" step="0.01" min="0.01" required :value="$expense->amount" />
        <x-form.input name="description" label="Açıklama" required maxlength="255" :value="$expense->description" placeholder="Örn. Ekim ayı kirası" class="sm:col-span-2" />
        <x-form.input name="spent_at" label="Tarih" type="date" required :value="$expense->spent_at?->toDateString()" :max="today()->toDateString()" />
        <x-form.select name="method" label="Ödeme yöntemi" required :options="App\Enums\PaymentMethod::options()" :value="$expense->method" />
    </div>

    <x-form.textarea name="notes" label="Not" :value="$expense->notes" rows="2" />

    <x-form.actions :cancel="route('expenses.index')" />
</div>
