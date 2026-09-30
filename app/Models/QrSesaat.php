<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrSesaat extends Model
{
    use HasFactory;

    protected $table = 'qr_sesaat';

    protected $fillable = [
        'token',
        'jenis',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];
}
