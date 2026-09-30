<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasangan_keluar_masuk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->unsignedBigInteger('pindaian_keluar_id')->nullable()->index();
            $table->dateTime('jam_keluar');
            $table->unsignedBigInteger('pindaian_masuk_id')->nullable()->index();
            $table->dateTime('jam_kembali')->nullable();
            $table->integer('durasi_menit')->nullable();
            $table->enum('status', ['terbuka', 'kembali', 'belum_kembali'])->default('terbuka');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['pegawai_id', 'status']);
            $table->index('jam_keluar');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasangan_keluar_masuk');
    }
};
