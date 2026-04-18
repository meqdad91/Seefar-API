<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'course';
    public $timestamps = false;

    public function categoryInfo()
    {
        return $this->belongsTo(CourseCategory::class, 'category');
    }

    public function enrolMethods()
    {
        return $this->hasMany(Enrol::class, 'courseid');
    }

    public function modules()
    {
        return $this->hasMany(CourseModule::class, 'course');
    }

    public function gradeItems()
    {
        return $this->hasMany(GradeItem::class, 'courseid');
    }
}
