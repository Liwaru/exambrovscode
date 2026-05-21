<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'external_source',
        'external_exam_id',
        'title',
        'class_name',
        'exam_date',
        'start_time',
        'end_time',
        'entry_pin',
        'exit_pin',
        'status',
        'callback_url',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
        ];
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function participants()
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ExamActivityLog::class);
    }
}
