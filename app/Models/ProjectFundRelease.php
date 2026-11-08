<?php

namespace App\Models;

class ProjectFundRelease extends ChapterRecord
{
    protected $casts = [
        'amount' => 'decimal:2',
        'release_date' => 'date',
        'confirmed_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function project() { return $this->belongsTo(Project::class)->withTrashed(); }
    public function chair() { return $this->belongsTo(User::class, 'chair_id')->withTrashed(); }
    public function treasurer() { return $this->belongsTo(User::class, 'initiated_by')->withTrashed(); }
    public function ledgerEntry() { return $this->belongsTo(LedgerEntry::class); }
}
