<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $table = 'quiz';
    public $timestamps = false;

    protected $hidden = ['password'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class, 'quiz');
    }
}
