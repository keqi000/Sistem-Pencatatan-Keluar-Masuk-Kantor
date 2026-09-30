<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izin_dinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('atasan_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('perkiraan_jam_pergi');
            $table->time('perkiraan_jam_kembali');
            $table->string('tujuan', 255);
            $table->text('keperluan');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan_atasan')->nullable();
            $table->timestamps();

            $table->index(['pegawai_id', 'tanggal']);
            $table->index(['atasan_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_dinas');
    }
};
