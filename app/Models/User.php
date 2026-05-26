<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const LEVEL_STUDENT = 1;
    public const LEVEL_TEACHER = 2;
    public const LEVEL_ADMIN = 3;
    public const LEVEL_HEADMASTER = 4;

    protected $primaryKey = 'id_user';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'class_name',
        'level',
        'status',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'api_token',
    ];

    public function supervisedExamSessions()
    {
        return $this->hasMany(ExamSession::class, 'teacher_id');
    }

    public function examParticipations()
    {
        return $this->hasMany(ExamParticipant::class, 'student_id');
    }

    public function isStudent(): bool
    {
        return (int) $this->level === self::LEVEL_STUDENT;
    }

    public function isTeacher(): bool
    {
        return (int) $this->level === self::LEVEL_TEACHER;
    }

    public function isAdmin(): bool
    {
        return (int) $this->level === self::LEVEL_ADMIN;
    }

    public function isHeadmaster(): bool
    {
        return (int) $this->level === self::LEVEL_HEADMASTER;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'level' => 'integer',
        ];
    }
}
