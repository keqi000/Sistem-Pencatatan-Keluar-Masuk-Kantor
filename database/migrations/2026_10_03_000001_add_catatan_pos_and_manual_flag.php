<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom catatan_pos di pindaian
        Schema::table('pindaian', function (Blueprint $table) {
            $table->text('catatan_pos')->nullable()->after('pasangan_id');
        });

        // Tambah kolom catatan_pos di pasangan_keluar_masuk (untuk catatan manual pos)
        Schema::table('pasangan_keluar_masuk', function (Blueprint $table) {
            $table->text('catatan_pos')->nullable()->after('status');
            $table->boolean('is_manual_pos')->default(false)->after('catatan_pos');
        });

        // SQLite tidak support ALTER COLUMN untuk enum,
        // tapi keperluan_jenis sudah nullable string — cukup allow nilai baru di app layer
    }

    public function down(): void
    {
        Schema::table('pindaian', function (Blueprint $table) {
            $table->dropColumn('catatan_pos');
        });
        Schema::table('pasangan_keluar_masuk', function (Blueprint $table) {
            $table->dropColumn(['catatan_pos', 'is_manual_pos']);
        });
    }
};
