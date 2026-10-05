@csrf

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="name" label="Ad soyad" :value="$user->name" required maxlength="255" class="sm:col-span-2" />
        <x-form.input name="email" label="E-posta (giriş için)" type="email" :value="$user->email" required autocomplete="off" />
        <x-form.input name="phone" label="Telefon" type="tel" :value="$user->phone" />
        <x-form.select name="role" label="Rol" required :options="App\Enums\UserRole::options()" :value="$user->role" class="sm:col-span-2" />
    </div>

    <div class="rounded-lg bg-gray-50 p-4 text-xs text-gray-600">
        <p><strong>Yönetici:</strong> tüm modüller, kullanıcılar ve klinik ayarları.</p>
        <p class="mt-1"><strong>Sekreter:</strong> hastalar, randevular ve ödemeler.</p>
        <p class="mt-1"><strong>Doktor:</strong> hastaları görüntüleme, kendi randevuları ve tedavi kayıtları.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.input name="password" label="Şifre" type="password" :required="! $user->exists" autocomplete="new-password"
                      :hint="$user->exists ? 'Değiştirmek istemiyorsanız boş bırakın.' : 'En az 8 karakter.'" />
        <x-form.input name="password_confirmation" label="Şifre (tekrar)" type="password" :required="! $user->exists" autocomplete="new-password" />
    </div>

    <x-form.checkbox name="is_active" label="Hesap aktif (panele giriş yapabilir)" :checked="$user->is_active" />

    <x-form.actions :cancel="route('users.index')" />
</div>
