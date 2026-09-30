<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawai';

    protected $fillable = [
        'nip',
        'nama_lengkap',
        'jabatan',
        'unit_kerja_id',
        'atasan_id',
        'nomor_hp',
        'foto',
        'status',
    ];

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function atasan()
    {
        return $this->belongsTo(Pegawai::class, 'atasan_id');
    }

    public function bawahan()
    {
        return $this->hasMany(Pegawai::class, 'atasan_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'pegawai_id');
    }

    public function izinDinas()
    {
        return $this->hasMany(IzinDinas::class, 'pegawai_id');
    }

    public function pindaians()
    {
        return $this->hasMany(Pindaian::class, 'pegawai_id');
    }

    public function pasanganKeluarMasuk()
    {
        return $this->hasMany(PasanganKeluarMasuk::class, 'pegawai_id');
    }
}
