<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends ChapterRecord
{
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];
}
