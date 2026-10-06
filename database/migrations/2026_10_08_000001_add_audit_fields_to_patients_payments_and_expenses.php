<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doktor, kendi açtığı (henüz randevusu olmayan) hasta kaydını görebilsin.
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('medical_notes')->constrained('users')->nullOnDelete();
        });

        // Mali kayıtlar kalıcı silinmez; kimin sildiği tutulur.
        foreach (['payments', 'expenses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->softDeletes();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['payments', 'expenses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('deleted_by');
                $table->dropSoftDeletes();
            });
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
