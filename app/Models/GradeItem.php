<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeItem extends Model
{
    protected $table = 'grade_items';
    public $timestamps = false;

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseid');
    }

    public function grades()
    {
        return $this->hasMany(GradeGrade::class, 'itemid');
    }
}
