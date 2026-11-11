<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tiket', 30)->nullable()->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->string('kategori', 50);
            $table->string('lokasi');
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->enum('status', ['Menunggu', 'Diverifikasi', 'Dikerjakan', 'Selesai', 'Ditolak'])
                ->default('Menunggu');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('komentar_feedback')->nullable();
            $table->timestamp('feedback_pada')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};
