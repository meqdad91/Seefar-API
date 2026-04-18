<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleAssignment extends Model
{
    protected $table = 'role_assignments';
    public $timestamps = false;

    public function role()
    {
        return $this->belongsTo(Role::class, 'roleid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    public function context()
    {
        return $this->belongsTo(Context::class, 'contextid');
    }
}
