<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends ChapterRecord
{
    protected $casts = ['assignees' => 'array', 'dependencies' => 'array', 'starts_on' => 'date', 'deadline' => 'date'];
}
