<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CohortMember extends Model
{
    protected $table = 'cohort_members';
    public $timestamps = false;

    public function cohort()
    {
        return $this->belongsTo(Cohort::class, 'cohortid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
