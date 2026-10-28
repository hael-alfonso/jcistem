<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends ChapterRecord
{
    protected $casts = ['assignment_responses' => 'array', 'assignees' => 'array', 'dependencies' => 'array', 'starts_on' => 'date', 'deadline' => 'date'];
}
