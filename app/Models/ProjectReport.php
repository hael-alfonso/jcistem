<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectReport extends ChapterRecord
{
    protected $casts = ['data' => 'array', 'versions' => 'array'];
}
