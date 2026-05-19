<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'student_id',
        'student_username',
        'device_id',
        'device_name',
        'event_type',
        'message',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
