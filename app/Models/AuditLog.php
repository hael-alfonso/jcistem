<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends ChapterRecord
{
    protected $casts = ['before' => 'array', 'after' => 'array'];
    public function actor() { return $this->belongsTo(User::class, 'user_id'); }
}
