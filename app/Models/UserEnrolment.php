<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEnrolment extends Model
{
    protected $table = 'user_enrolments';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    public function enrol()
    {
        return $this->belongsTo(Enrol::class, 'enrolid');
    }
}
