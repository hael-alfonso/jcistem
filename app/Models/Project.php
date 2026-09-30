<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends ChapterRecord
{
    protected $casts = ['concept' => 'array', 'proposal' => 'array', 'starts_on' => 'date', 'ends_on' => 'date', 'budget_reviewed_at' => 'datetime', 'proposed_budget' => 'decimal:2'];
    public const APPROVED = ['Approved', 'Ongoing', 'Completion Review', 'Completed', 'Archived'];
    public function owner() { return $this->belongsTo(User::class, 'created_by'); }
    public function chair() { return $this->belongsTo(User::class, 'chair_id'); }
    public function tasks() { return $this->hasMany(Task::class); }
    public function reviews() { return $this->hasMany(ProjectReview::class); }
    public function allocations() { return $this->hasMany(BudgetAllocation::class); }
    public function transactions() { return $this->hasMany(LedgerEntry::class); }
    public function documents() { return $this->hasMany(ProjectDocument::class); }
    public function letters() { return $this->hasMany(Loi::class); }
    public function reports() { return $this->hasMany(ProjectReport::class); }
    public function events() { return $this->hasMany(CalendarEvent::class); }
    public function getAllocatedAttribute(): float { return (float) $this->allocations()->sum('amount'); }
    public function getSpentAttribute(): float { return (float) $this->transactions()->where('direction', 'debit')->where('status', 'Posted')->sum('amount'); }
    public function getRemainingAttribute(): float { return round($this->allocated - $this->spent, 2); }
    public function getProgressAttribute(): int {
        $total = $this->tasks()->count();
        return $total ? (int) round(100 * $this->tasks()->where('status', 'Completed')->count() / $total) : 0;
    }
    public function canManage(User $user): bool { return $this->chair_id === $user->id && in_array($this->status, ['Approved', 'Ongoing'], true); }
    public function canEdit(User $user): bool { return $this->created_by === $user->id && in_array($this->status, ['Draft Concept', 'Needs Revision', 'Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision'], true); }
}
