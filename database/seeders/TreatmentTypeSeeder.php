<?php

namespace Database\Seeders;

use App\Models\TreatmentType;
use Illuminate\Database\Seeder;

class TreatmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Muayene' => 500,
            'Dolgu' => 2500,
            'Kanal tedavisi' => 6000,
            'Diş çekimi' => 2000,
            'İmplant' => 25000,
            'Diş taşı temizliği' => 1500,
            'Zirkonyum kaplama' => 9000,
            'Beyazlatma' => 5000,
        ];

        foreach ($types as $name => $price) {
            TreatmentType::query()->firstOrCreate(['name' => $name], ['default_price' => $price]);
        }
    }
}
