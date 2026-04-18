<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'role';
    public $timestamps = false;

    public function assignments()
    {
        return $this->hasMany(RoleAssignment::class, 'roleid');
    }
}
