<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_user_id',
        'actor_username',
        'actor_level',
        'event_type',
        'description',
        'subject_table',
        'subject_id',
        'recoverable',
        'restored_at',
        'restored_by',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'actor_level' => 'integer',
            'recoverable' => 'boolean',
            'restored_at' => 'datetime',
            'properties' => 'array',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'id_user')->withTrashed();
    }

    public function restoredByUser()
    {
        return $this->belongsTo(User::class, 'restored_by', 'id_user')->withTrashed();
    }

    public static function record(
        string $eventType,
        string $description,
        ?Request $request = null,
        array $options = []
    ): self {
        $actor = $options['actor'] ?? ($request?->user() ?: auth()->user());

        return self::create([
            'actor_user_id' => $actor?->getKey(),
            'actor_username' => $actor?->username,
            'actor_level' => $actor?->level,
            'event_type' => $eventType,
            'description' => $description,
            'subject_table' => $options['subject_table'] ?? null,
            'subject_id' => $options['subject_id'] ?? null,
            'recoverable' => $options['recoverable'] ?? false,
            'properties' => $options['properties'] ?? null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
