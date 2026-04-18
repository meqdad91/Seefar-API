<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $table = 'course_modules';
    public $timestamps = false;

    public function course()
    {
        return $this->belongsTo(Course::class, 'course');
    }

    public function moduleType()
    {
        return $this->belongsTo(Module::class, 'module');
    }
}
