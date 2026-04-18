<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cohort extends Model
{
    protected $table = 'cohort';
    public $timestamps = false;

    public function members()
    {
        return $this->hasMany(CohortMember::class, 'cohortid');
    }
}
