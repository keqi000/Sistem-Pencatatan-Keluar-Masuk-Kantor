<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'target_table',
        'target_id',
        'keterangan',
        'ip_address',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(string $action, ?string $targetTable = null, ?int $targetId = null, ?string $keterangan = null): void
    {
        try {
            self::create([
                'user_id'      => auth()->id(),
                'action'       => $action,
                'target_table' => $targetTable,
                'target_id'    => $targetId,
                'keterangan'   => $keterangan,
                'ip_address'   => request()->ip(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error("Failed to log activity: " . $e->getMessage());
        }
    }
}
