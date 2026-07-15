<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action', 'subject_type', 'subject_id', 'context', 'admin_ip', 'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(string $action, ?Model $subject = null, array $context = []): self
    {
        return self::create([
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'context' => $context ?: null,
            'admin_ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
