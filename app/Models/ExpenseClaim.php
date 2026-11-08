<?php

namespace App\Models;

class ExpenseClaim extends ChapterRecord
{
    protected $casts = ['amount' => 'decimal:2', 'spent_on' => 'date', 'reviewed_at' => 'datetime'];
    public function project() { return $this->belongsTo(Project::class)->withTrashed(); }

    public function submitter() { return $this->belongsTo(User::class, 'submitted_by')->withTrashed(); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by')->withTrashed(); }
    public function ledgerEntry() { return $this->belongsTo(LedgerEntry::class); }
}
