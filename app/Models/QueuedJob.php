<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueuedJob extends Model
{
    protected $table = 'jobs';
    public $timestamps = false;
}
