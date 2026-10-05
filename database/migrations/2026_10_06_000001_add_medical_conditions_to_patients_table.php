<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Anamnez kontrol listesi: App\Enums\MedicalCondition değerlerinden oluşan dizi.
            $table->json('medical_conditions')->nullable()->after('allergies');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('medical_conditions');
        });
    }
};
