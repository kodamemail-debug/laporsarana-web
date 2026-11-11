<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identitas_sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_identitas', 50)->unique();
            $table->string('nama_lengkap');
            $table->enum('jenis_pengguna', ['Siswa', 'Guru']);
            $table->string('kelas', 30)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identitas_sekolah');
    }
};
