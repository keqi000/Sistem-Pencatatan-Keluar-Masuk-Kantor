<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    use HasFactory;

    protected $table = 'unit_kerja';

    protected $fillable = [
        'nama_unit',
        'kode_unit',
        'pimpinan_id',
    ];

    public function pimpinan()
    {
        return $this->belongsTo(User::class, 'pimpinan_id');
    }

    public function pegawais()
    {
        return $this->hasMany(Pegawai::class, 'unit_kerja_id');
    }
}
