<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectReport extends ChapterRecord
{
    protected $casts = ['reviewed_at' => 'datetime', 'data' => 'array', 'versions' => 'array'];
}
