<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pindaian extends Model
{
    use HasFactory;

    protected $table = 'pindaian';

    protected $fillable = [
        'pegawai_id',
        'jenis',
        'jam',
        'tempat',
        'keperluan_jenis',
        'izin_dinas_id',
        'pasangan_id',
    ];

    protected $casts = [
        'jam' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function izinDinas()
    {
        return $this->belongsTo(IzinDinas::class, 'izin_dinas_id');
    }

    public function pasangan()
    {
        return $this->belongsTo(PasanganKeluarMasuk::class, 'pasangan_id');
    }
}
