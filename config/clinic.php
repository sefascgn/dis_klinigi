<?php

return [

    /*
    |--------------------------------------------------------------------------
    | İlk yönetici hesabı
    |--------------------------------------------------------------------------
    |
    | `php artisan db:seed` ile oluşturulan ilk yönetici. Production'da
    | .env içinde mutlaka güçlü bir ADMIN_PASSWORD tanımlayın.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@klinik.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
