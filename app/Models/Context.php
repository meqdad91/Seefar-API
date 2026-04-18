<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Context extends Model
{
    protected $table = 'context';
    public $timestamps = false;

    public const LEVEL_SYSTEM = 10;
    public const LEVEL_USER = 30;
    public const LEVEL_COURSECAT = 40;
    public const LEVEL_COURSE = 50;
    public const LEVEL_MODULE = 70;
    public const LEVEL_BLOCK = 80;
}
