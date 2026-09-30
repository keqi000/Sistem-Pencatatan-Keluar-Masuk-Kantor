<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pindaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->enum('jenis', ['keluar', 'masuk']);
            $table->dateTime('jam');
            $table->enum('tempat', ['lobby', 'pos']);
            $table->enum('keperluan_jenis', ['dinas', 'keperluan_lain'])->nullable();
            $table->foreignId('izin_dinas_id')->nullable()->constrained('izin_dinas')->nullOnDelete();
            $table->foreignId('pasangan_id')->nullable()->constrained('pasangan_keluar_masuk')->nullOnDelete();
            $table->timestamps();

            $table->index(['pegawai_id', 'jam']);
            $table->index('jam');
            $table->index('jenis');
            $table->index('tempat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pindaian');
    }
};
