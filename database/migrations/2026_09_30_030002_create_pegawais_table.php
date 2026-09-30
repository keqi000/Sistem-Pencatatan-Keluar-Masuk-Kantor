<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->unique();
            $table->string('nama_lengkap', 150);
            $table->string('jabatan', 150);
            $table->foreignId('unit_kerja_id')->constrained('unit_kerja')->restrictOnDelete();
            $table->foreignId('atasan_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $table->string('nomor_hp', 25)->nullable();
            $table->string('foto', 255)->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif'])->default('aktif');
            $table->timestamps();

            $table->index('unit_kerja_id');
            $table->index('atasan_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
