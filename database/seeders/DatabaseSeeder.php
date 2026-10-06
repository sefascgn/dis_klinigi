<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ClinicSetting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Her ortamda gerekli temel veriler eklenir; demo verisi yalnızca local ortamda.
     */
    public function run(): void
    {
        ClinicSetting::query()->firstOrCreate([], ['name' => config('app.name')]);

        $this->call(TreatmentTypeSeeder::class);

        $admin = User::query()->firstOrNew(['email' => config('clinic.admin.email')]);
        if (! $admin->exists) {
            $admin->name = 'Klinik Yöneticisi';
            $admin->password = $this->adminPassword();
            $admin->role = UserRole::Admin;
            $admin->is_active = true;
            $admin->email_verified_at = now();
            $admin->save();
        }

        if (app()->isLocal()) {
            $this->call([DemoSeeder::class, ExpenseDemoSeeder::class]);
        }
    }

    /**
     * Local'de ADMIN_PASSWORD boşsa demo şifresi "password" kullanılır.
     * Diğer ortamlarda zayıf / boş şifreyle yönetici oluşturulmaz.
     */
    private function adminPassword(): string
    {
        $password = (string) config('clinic.admin.password');

        if (app()->isLocal()) {
            return $password !== '' ? $password : 'password';
        }

        if (mb_strlen($password) < 12 || $password === 'password') {
            throw new RuntimeException('Yönetici oluşturulamadı: .env içinde en az 12 karakterlik güçlü bir ADMIN_PASSWORD tanımlayın.');
        }

        return $password;
    }
}
