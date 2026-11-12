<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends ChapterRecord
{
    use SoftDeletes;

    protected $casts = ['assignment_responses' => 'array', 'assignees' => 'array', 'dependencies' => 'array', 'starts_on' => 'date', 'deadline' => 'date'];

    public function nextStep(User $user): string
    {
        if ($this->status === 'Completed') return 'Task complete';
        if (!in_array($this->project->status, ['Plan Locked', 'Ongoing'], true) || !$this->project->plan_locked_at) return 'Task updates closed at this project stage';
        $responses = $this->assignment_responses ?? [];
        if (in_array($user->id, $this->assignees ?? [], true)) {
            $response = $responses[$user->id]['response'] ?? null;
            if ($response === 'declined') return 'Assignment declined — contact the Chair';
            if ($response !== 'accepted') return 'Accept your assignment';
        }
        if ($this->project->status === 'Plan Locked') return 'Waiting for project launch';
        if (!$this->assignees) return 'Chair needs to assign members';
        if (collect($this->assignees)->contains(fn ($id) => ($responses[$id]['response'] ?? null) === 'declined')) return 'Chair needs to review declined assignments';
        if (!collect($this->assignees)->contains(fn ($id) => ($responses[$id]['response'] ?? null) === 'accepted')) return 'Waiting for assignment acceptance';
        if ($this->project->tasks->whereIn('id', $this->dependencies ?? [])->where('status', 'Completed')->count() !== count($this->dependencies ?? [])) return 'Waiting for prerequisite tasks';
        if ($this->status === 'Blocked') return 'Resolve the blocker with the Chair';
        return $this->status === 'In Progress' ? 'Finish work and add completion evidence' : 'Ready to start';
    }
}
