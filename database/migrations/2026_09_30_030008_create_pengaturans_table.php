<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 64)->unique();
            $table->text('setting_value');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index('setting_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
