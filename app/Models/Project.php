<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends ChapterRecord
{
    use SoftDeletes;
    protected $casts = ['concept' => 'array', 'proposal' => 'array', 'budget_items' => 'array', 'starts_on' => 'date', 'ends_on' => 'date', 'completed_at' => 'datetime', 'archived_at' => 'datetime', 'plan_locked_at' => 'datetime', 'budget_reviewed_at' => 'datetime', 'proposal_budget_reviewed_at' => 'datetime', 'proposal_reviewed_at' => 'datetime', 'proposed_budget' => 'decimal:2'];
    public const APPROVED = ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review', 'Completed', 'Archived'];
    public static function toneForStatus(string $status): string
    {
        return match ($status) {
            'Draft Concept', 'Full Proposal Draft' => 'draft',
            'Admin Review', 'Treasurer Review', 'BOD Agenda', 'Submitted for President Review', 'Submitted for Formal Approval', 'Completion Review' => 'review',
            'Needs Revision', 'Returned for Revision' => 'revision',
            'Endorsed for Development', 'Approved', 'Plan Locked' => 'approved',
            'Ongoing' => 'ongoing',
            'Completed' => 'completed',
            'Declined', 'Not Approved', 'Cancelled' => 'declined',
            'Archived' => 'archived',
            default => 'draft',
        };
    }
    public static function colorForStatus(string $status): string
    {
        return [
            'draft' => '#6655d8', 'review' => '#d68a12', 'revision' => '#dd6326',
            'approved' => '#0769bd', 'ongoing' => '#089aaa', 'completed' => '#17834f',
            'declined' => '#c93450', 'archived' => '#566c7e',
        ][self::toneForStatus($status)];
    }
    public function getStatusToneAttribute(): string { return self::toneForStatus($this->status); }
    public function owner() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function chair() { return $this->belongsTo(User::class, 'chair_id')->withTrashed(); }
    public function tasks() { return $this->hasMany(Task::class); }
    public function reviews() { return $this->hasMany(ProjectReview::class); }
    public function allocations() { return $this->hasMany(BudgetAllocation::class); }
    public function transactions() { return $this->hasMany(LedgerEntry::class); }
    public function expenseClaims() { return $this->hasMany(ExpenseClaim::class); }
    public function fundReleases() { return $this->hasMany(ProjectFundRelease::class); }
    public function documents() { return $this->hasMany(ProjectDocument::class); }
    public function letters() { return $this->hasMany(Loi::class); }
    public function reports() { return $this->hasMany(ProjectReport::class); }
    public function events() { return $this->hasMany(CalendarEvent::class); }
    public function getAllocatedAttribute(): float { return (float) $this->allocations()->sum('amount'); }
    public function getSpentAttribute(): float { return (float) $this->transactions()->where('direction', 'debit')->where('status', 'Posted')->sum('amount'); }
    public function getRemainingAttribute(): float { return round($this->allocated - $this->spent, 2); }
    public function getProgressAttribute(): int {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $completed = $this->completed_tasks_count ?? $this->tasks()->where('status', 'Completed')->count();
        return \App\Support\ProjectProgress::summarize($this, (int) $completed, (int) $total)['percent'];
    }
    public function decisions() { return $this->hasMany(BodDecision::class); }
    public function canCancel(User $user): bool {
        return !$this->trashed() && !$this->archived_at && !in_array($this->status, ['Completed', 'Archived', 'Cancelled', 'Declined', 'Not Approved'], true) && $user->status === 'active'
            && ($user->role === 'admin' || ($this->chair_id === $user->id && $this->decisions()->where('decision', 'approved')->exists()));
    }
    public function isChair(User $user): bool { return $user->status === 'active' && $this->chair_id === $user->id && in_array($this->status, self::APPROVED, true) && $this->decisions()->where('decision', 'approved')->exists(); }
    public function canManage(User $user): bool { return $this->isChair($user) && $this->plan_locked_at && in_array($this->status, ['Plan Locked', 'Ongoing'], true); }
    public function canReport(User $user): bool { return $this->isChair($user) && $this->status === 'Completion Review'; }
    public function canEdit(User $user): bool { return $user->status === 'active' && (($this->created_by === $user->id && in_array($this->status, ['Draft Concept', 'Needs Revision'], true)) || ($this->isChair($user) && $this->status === 'Approved' && !$this->proposal_reviewed_at && !$this->plan_locked_at)); }
}
