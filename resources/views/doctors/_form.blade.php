@csrf

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
        <x-form.input name="title" label="Unvan" :value="$doctor->title" placeholder="Dt., Uzm. Dt." maxlength="50" class="sm:col-span-2" />
        <x-form.input name="first_name" label="Ad" :value="$doctor->first_name" required maxlength="100" class="sm:col-span-2" />
        <x-form.input name="last_name" label="Soyad" :value="$doctor->last_name" required maxlength="100" class="sm:col-span-2" />
        <x-form.input name="specialty" label="Uzmanlık" :value="$doctor->specialty" placeholder="Örn. Ortodonti" maxlength="150" class="sm:col-span-6" list="specialties" />
        <x-form.input name="phone" label="Telefon" type="tel" :value="$doctor->phone" class="sm:col-span-3" />
        <x-form.input name="email" label="E-posta" type="email" :value="$doctor->email" class="sm:col-span-3" />
        <x-form.select name="user_id" label="Panel hesabı" class="sm:col-span-6"
                       :options="$users->mapWithKeys(fn ($u) => [$u->id => $u->name.' ('.$u->email.')'])->all()"
                       :value="$doctor->user_id" placeholder="Hesap bağlanmasın" />
        <p class="-mt-2 text-xs text-gray-500 sm:col-span-6">
            Doktorun panele giriş yapıp kendi randevu ve tedavilerini yönetebilmesi için “Doktor” rolündeki bir kullanıcı hesabını bağlayın.
        </p>
        <x-form.checkbox name="is_active" label="Aktif (randevu verilebilir)" :checked="$doctor->is_active" class="sm:col-span-6" />
    </div>

    <datalist id="specialties">
        @foreach (['Genel Diş Hekimliği', 'Ortodonti', 'Endodonti', 'Periodontoloji', 'Ağız, Diş ve Çene Cerrahisi', 'Pedodonti', 'Protetik Diş Tedavisi', 'Restoratif Diş Tedavisi', 'Ağız, Diş ve Çene Radyolojisi'] as $specialty)
            <option value="{{ $specialty }}">
        @endforeach
    </datalist>

    <x-form.actions :cancel="$doctor->exists ? route('doctors.show', $doctor) : route('doctors.index')" />
</div>
