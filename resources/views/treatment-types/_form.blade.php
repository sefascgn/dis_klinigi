@csrf

<div class="space-y-5">
    <x-form.input name="name" label="İşlem adı" :value="$treatmentType->name" required maxlength="150" placeholder="Örn. Kanal tedavisi" />
    <x-form.input name="default_price" label="Varsayılan ücret (₺)" type="number" step="0.01" min="0" required :value="$treatmentType->default_price"
                  hint="Tedavi kaydı oluşturulurken otomatik önerilir; kayıt sırasında değiştirilebilir." />
    <x-form.textarea name="description" label="Açıklama" :value="$treatmentType->description" rows="3" />
    <x-form.checkbox name="is_active" label="Aktif (yeni tedavilerde seçilebilir)" :checked="$treatmentType->is_active" />

    <x-form.actions :cancel="route('treatment-types.index')" />
</div>
