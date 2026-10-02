<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberDue extends ChapterRecord
{
    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date'];
    public function member() { return $this->belongsTo(User::class, 'member_id'); }
    public function payments() { return $this->hasMany(LedgerEntry::class); }
    public function getPaidAttribute(): float
    {
        return (float) ($this->relationLoaded('payments')
            ? $this->payments->where('status', 'Posted')->sum('amount')
            : $this->payments()->where('status', 'Posted')->sum('amount'));
    }
    public function getBalanceAttribute(): float { return max(0, round($this->amount - $this->paid, 2)); }
    public function getStatusAttribute(): string { return $this->balance <= 0 ? 'Paid' : ($this->due_date?->lt(today()) ? 'Overdue' : ($this->paid > 0 ? 'Partial' : 'Unpaid')); }
}
