<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mysql';
    protected $table = 'user';
    public $timestamps = false;

    protected $hidden = [
        'password',
        'secret',
        'lastip',
    ];

    public function enrolments()
    {
        return $this->hasMany(UserEnrolment::class, 'userid');
    }

    public function roleAssignments()
    {
        return $this->hasMany(RoleAssignment::class, 'userid');
    }

    public function grades()
    {
        return $this->hasMany(GradeGrade::class, 'userid');
    }
}
