<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->foreignId('treatment_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            // FDI diş numarası (11-48 kalıcı, 51-85 süt dişi). Genel işlemlerde boş kalır.
            $table->unsignedTinyInteger('tooth_number')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('status', 20)->default('planned');
            $table->date('treatment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'treatment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
