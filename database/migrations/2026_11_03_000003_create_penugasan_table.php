<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
            $table->foreignId('teknisi_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Ditugaskan', 'Dikerjakan', 'Selesai'])->default('Ditugaskan');
            $table->text('catatan_tindakan')->nullable();
            $table->string('foto_hasil')->nullable();
            $table->timestamp('mulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();

            $table->unique(['pengaduan_id', 'teknisi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan');
    }
};
