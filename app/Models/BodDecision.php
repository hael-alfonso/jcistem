<?php

namespace App\Models;

class BodDecision extends ChapterRecord
{
    protected $casts = ['meeting_date' => 'date'];
    public function project() { return $this->belongsTo(Project::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
