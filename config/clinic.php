<?php

return [

    /*
    |--------------------------------------------------------------------------
    | İlk yönetici hesabı
    |--------------------------------------------------------------------------
    |
    | `php artisan db:seed` ile oluşturulan ilk yönetici. Varsayılan şifre
    | yoktur: local dışındaki ortamlarda ADMIN_PASSWORD en az 12 karakter
    | olmalıdır, aksi halde seeder durur (bkz. DatabaseSeeder).
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@klinik.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
