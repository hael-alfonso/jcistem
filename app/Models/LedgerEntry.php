<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends ChapterRecord
{
    protected $casts = ['amount' => 'decimal:2', 'transaction_date' => 'date'];
    public function member() { return $this->belongsTo(User::class, 'member_id'); }
    public function due() { return $this->belongsTo(MemberDue::class, 'member_due_id'); }
}
