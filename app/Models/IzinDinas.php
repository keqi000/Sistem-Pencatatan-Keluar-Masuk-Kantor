<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinDinas extends Model
{
    use HasFactory;

    protected $table = 'izin_dinas';

    protected $fillable = [
        'pegawai_id',
        'atasan_id',
        'tanggal',
        'perkiraan_jam_pergi',
        'perkiraan_jam_kembali',
        'tujuan',
        'keperluan',
        'status',
        'catatan_atasan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function atasan()
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }
}
