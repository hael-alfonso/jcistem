<?php

namespace App\Models;

class ExpenseClaim extends ChapterRecord
{
    protected $casts = ['amount' => 'decimal:2', 'spent_on' => 'date', 'reviewed_at' => 'datetime'];

    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function ledgerEntry() { return $this->belongsTo(LedgerEntry::class); }
}
