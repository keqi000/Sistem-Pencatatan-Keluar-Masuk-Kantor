<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasanganKeluarMasuk extends Model
{
    use HasFactory;

    protected $table = 'pasangan_keluar_masuk';

    protected $fillable = [
        'pegawai_id',
        'pindaian_keluar_id',
        'jam_keluar',
        'pindaian_masuk_id',
        'jam_kembali',
        'durasi_menit',
        'status',
        'catatan',
        'catatan_pos',
        'is_manual_pos',
    ];

    protected $casts = [
        'jam_keluar'  => 'datetime',
        'jam_kembali' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function pindaianKeluar()
    {
        return $this->belongsTo(Pindaian::class, 'pindaian_keluar_id');
    }

    public function pindaianMasuk()
    {
        return $this->belongsTo(Pindaian::class, 'pindaian_masuk_id');
    }
}
