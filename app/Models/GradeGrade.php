<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeGrade extends Model
{
    protected $table = 'grade_grades';
    public $timestamps = false;

    public function item()
    {
        return $this->belongsTo(GradeItem::class, 'itemid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
