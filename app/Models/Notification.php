<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends ChapterRecord
{
    protected $casts = ['read_at' => 'datetime'];
}
