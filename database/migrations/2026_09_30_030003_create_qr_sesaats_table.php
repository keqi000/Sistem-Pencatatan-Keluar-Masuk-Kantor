<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_sesaat', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->enum('jenis', ['keluar', 'masuk']);
            $table->dateTime('expired_at');
            $table->timestamps();

            $table->index(['jenis', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_sesaat');
    }
};
