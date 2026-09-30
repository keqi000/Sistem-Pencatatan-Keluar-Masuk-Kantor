<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    use HasFactory;

    protected $table = 'pengaturan';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'keterangan',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('setting_key', $key)->first();
        return $setting ? $setting->setting_value : $default;
    }

    public static function set(string $key, $value, ?string $keterangan = null): self
    {
        return self::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => (string)$value, 'keterangan' => $keterangan]
        );
    }
}
