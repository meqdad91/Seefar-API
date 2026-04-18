<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrol extends Model
{
    protected $table = 'enrol';
    public $timestamps = false;

    protected $hidden = ['password'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseid');
    }

    public function userEnrolments()
    {
        return $this->hasMany(UserEnrolment::class, 'enrolid');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'roleid');
    }
}
