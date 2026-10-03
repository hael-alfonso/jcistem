@extends('layouts.chapter')
@section('title','Project workspace')
@section('content')
@php $u=auth()->user(); $manages=$project->canManage($u); $canReview=$u->role==='admin'||($u->role==='bod'&&$u->proposal_reviewer); @endphp
<div class="page-heading"><div><a class="back-link" href="{{ route('projects') }}">← Projects</a><div class="eyebrow">{{ $project->reference }} · {{ $project->area }}</div><h1>{{ $project->title }}</h1><p>Proposed by {{ $project->owner?->name }} · {{ $project->starts_on?->format('M d, Y') ?? 'Date to be set' }} · {{ $project->venue??'Venue to be set' }}</p></div><div class="heading-actions"><span class="badge large project-status status-{{ $project->status_tone }}">{{ $project->status }}</span><button class="btn secondary" type="button" data-print>Print / save PDF</button></div></div>
@php
    $lastReview = $project->reviews->sortByDesc('id')->first();
    $trackerStatus = $project->status === 'Archived' ? ($lastReview?->from_status ?? 'Archived') : $project->status;
    $trackerStep = match ($trackerStatus) {
        'Draft Concept' => 1,
        'Submitted for President Review', 'Needs Revision', 'Declined' => 2,
        'Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision' => 3,
        'Submitted for Formal Approval', 'Not Approved' => 4,
        'Approved', 'Ongoing' => 5,
        default => 6,
    };
    $trackedTasks = $project->tasks;
    $completedTasks = $trackedTasks->where('status', 'Completed')->count();
    $nextTask = $trackedTasks->where('status', '!=', 'Completed')->sortBy('deadline')->first();
@endphp
<section class="panel project-tracker" aria-labelledby="project-tracker-title">
    <div class="tracker-heading"><div><span class="eyebrow">PROJECT MANAGEMENT</span><h2 id="project-tracker-title">Project tracker</h2><p>Current stage: {{ $project->status }}</p></div></div>
    <ol class="tracker-steps">
        @foreach(['Concept', 'President review', 'Full proposal', 'Final approval', 'Implementation', 'Completion'] as $index => $stage)
            @php $number = $index + 1; @endphp
            <li class="{{ $number < $trackerStep ? 'is-done' : ($number === $trackerStep ? 'is-current' : '') }}" @if($number === $trackerStep) aria-current="step" @endif><b>{{ $number }}</b><span>{{ $stage }}</span></li>
        @endforeach
    </ol>
    <div class="tracker-details">
        <div><span>Tasks completed</span><strong>{{ $completedTasks }} of {{ $trackedTasks->count() }}</strong><progress max="{{ max(1, $trackedTasks->count()) }}" value="{{ $completedTasks }}"></progress></div>
        <div><span>Next open task</span><strong>{{ $nextTask?->title ?? 'No open task' }}</strong><small>{{ $nextTask?->deadline?->format('M d, Y') ?? 'Add a task from the Tasks section.' }}</small></div>
        <div><span>Last updated</span><strong>{{ $project->updated_at?->format('M d, Y') ?? 'Not set' }}</strong><small>Project details and task status</small></div>
    </div>
</section>
<div class="stats-grid"><div class="stat-card"><span>Project Chair</span><strong class="name">{{ $project->chair?->name??'Unassigned' }}</strong><small>Assigned after final approval</small></div><div class="stat-card"><span>Estimated allocation</span><strong class="money">₱{{ number_format($project->allocated,2) }}</strong><small>Proposed: ₱{{ number_format($project->proposed_budget,2) }}</small></div><div class="stat-card"><span>Actual expenses</span><strong class="money">₱{{ number_format($project->spent,2) }}</strong><small>Posted ledger debits</small></div><div class="stat-card"><span>Remaining funds</span><strong class="money">₱{{ number_format($project->remaining,2) }}</strong><small>{{ $project->allocated>0?number_format(100*$project->spent/$project->allocated,1):'0' }}% utilization</small></div></div>
<section class="panel form-panel no-print"><h2>Available actions</h2><div class="action-grid">
@if($project->canEdit($u) && $project->status!=='Endorsed for Development')<a class="btn secondary" href="{{ route('projects.edit',$project) }}">Edit {{ in_array($project->status,['Draft Concept','Needs Revision'])?'concept':'proposal' }}</a>@endif
@if($project->created_by===$u->id && in_array($project->status,['Draft Concept','Needs Revision']))@include('chapter.partials.project-action',['action'=>'submit_concept','label'=>'Submit Concept to President','tone'=>'primary'])@endif
@if($u->concept_reviewer && in_array($u->role,['admin','bod']) && $project->status==='Submitted for President Review')
@foreach(['endorse'=>'Endorse for Development','revise_concept'=>'Return Concept for Revision','decline'=>'Decline Concept'] as $action=>$label)@include('chapter.partials.project-action',['comments'=>true])@endforeach
@endif
@if($project->created_by===$u->id && $project->status==='Endorsed for Development')@include('chapter.partials.project-action',['action'=>'start_proposal','label'=>'Create Full Proposal','tone'=>'primary'])@endif
@if($project->created_by===$u->id && in_array($project->status,['Full Proposal Draft','Returned for Revision']))@include('chapter.partials.project-action',['action'=>'submit_proposal','label'=>'Submit for Formal Approval','tone'=>'primary'])@endif
@if($project->status==='Submitted for Formal Approval')
@if($u->role==='treasurer')@include('chapter.partials.project-action',['action'=>'budget_review','label'=>'Record Budget Review','comments'=>true])@endif
@if($u->proposal_reviewer && in_array($u->role,['admin','bod']))@foreach(['approve'=>'Approve Project','revise_proposal'=>'Return Proposal for Revision','reject'=>'Do Not Approve'] as $action=>$label)@include('chapter.partials.project-action',['comments'=>true])@endforeach @endif
<span class="hint">Budget review: {{ $project->budget_reviewed_at?->format('M d, Y H:i') ?? 'Awaiting Treasurer' }}</span>
@endif
@if($canReview && in_array($project->status,['Approved','Ongoing']))<form method="POST" action="{{ route('projects.transition',$project) }}">@csrf<input type="hidden" name="action" value="assign_chair"><x-field name="chair_id" label="Assign project Chair" type="select" :options="$assignableMembers->pluck('name','id')->all()" :value="$project->chair_id" required/><button class="btn secondary">Assign Chair</button></form>@endif
@if($manages && $project->status==='Approved')@include('chapter.partials.project-action',['action'=>'start','label'=>'Start Implementation','tone'=>'primary'])@endif
@if($manages && $project->status==='Ongoing')@include('chapter.partials.project-action',['action'=>'request_completion','label'=>'Request Completion Review'])@endif
@if($u->role==='admin' && $project->status==='Completion Review')@include('chapter.partials.project-action',['action'=>'complete','label'=>'Mark Project Completed'])@include('chapter.partials.project-action',['action'=>'return_completion','label'=>'Return to Implementation','comments'=>true])@endif
@if($u->role==='admin' && in_array($project->status,['Completed','Declined','Not Approved']))@include('chapter.partials.project-action',['action'=>'archive','label'=>'Archive Project'])@endif
</div><p class="hint">Monitoring access does not grant editing, approval, or financial recording authority.</p></section>
<nav class="anchor-tabs no-print"><a href="#overview">Overview</a><a href="#tasks">Tasks</a><a href="#timeline">Timeline</a><a href="#budget">Budget & expenses</a><a href="#documents">Documents</a><a href="#reports">LOIs & reports</a></nav>
<section class="panel form-panel" id="overview"><h2>Project concept letter</h2><p class="letter-recipient">To: JCI Carmona Chapter President<br>From: {{ $project->owner?->name }}<br>Subject: Proposed Project — {{ $project->title }}</p><div class="detail-grid">@foreach(\App\Support\ChapterForms::CONCEPT as $key=>$label)<div><h3>{{ $label }}</h3><p class="preserve-lines">{{ $project->concept[$key]??'Not provided' }}</p></div>@endforeach</div></section>
@if($project->proposal)<section class="panel form-panel"><h2>Full project proposal</h2><div class="detail-grid">@foreach(\App\Support\ChapterForms::PROPOSAL as $key=>$label)<div><h3>{{ $label }}</h3><p class="preserve-lines">{{ $project->proposal[$key]??'Not provided' }}</p></div>@endforeach</div></section>@endif
<section class="panel form-panel project-tasks-section" id="tasks">
    <div class="panel-heading flush"><div><h2>Tasks &amp; milestones</h2><p>{{ $completedTasks }} of {{ $trackedTasks->count() }} completed</p></div><span>{{ $project->progress }}% complete</span></div>
    <progress value="{{ $project->progress }}" max="100" aria-label="Project task completion"></progress>
    <div class="project-task-list">
        @forelse($project->tasks as $task)
            @php
                $taskTone = match($task->status) { 'Completed' => 'done', 'In Progress' => 'working', 'Blocked' => 'blocked', default => 'todo' };
                $priorityTone = match($task->priority) { 'Urgent' => 'urgent', 'High' => 'high', 'Medium' => 'medium', 'Low' => 'low', default => 'unset' };
                $assignedNames = $members->whereIn('id', $task->assignees ?? [])->pluck('name')->join(', ');
                $isOverdue = $task->deadline && $task->deadline->lt(today()) && $task->status !== 'Completed';
                $canUpdateTask = $manages || (in_array($u->id, $task->assignees ?? [], true) && in_array($project->status, ['Approved', 'Ongoing']));
            @endphp
            <article class="project-task-card" id="task-{{ $task->id }}">
                <div class="project-task-heading"><div><h3>{{ $task->title }}</h3>@if($task->description)<p class="preserve-lines">{{ $task->description }}</p>@endif</div><span class="badge task-status task-status-{{ $taskTone }}">{{ $task->status }}</span></div>
                <dl class="project-task-meta">
                    <div><dt>Assigned to</dt><dd>{{ $assignedNames ?: 'Unassigned' }}</dd></div>
                    <div><dt>Deadline</dt><dd>{{ $task->deadline?->format('M d, Y') ?? 'Not set' }}@if($isOverdue) <span class="danger-text">· Overdue</span>@endif</dd></div>
                    <div><dt>Priority</dt><dd><span class="task-priority priority-{{ $priorityTone }}">{{ $task->priority ?: 'Not set' }}</span></dd></div>
                    <div><dt>Milestone</dt><dd>{{ $task->milestone ?: 'None' }}</dd></div>
                </dl>
                @if($task->notes)<div class="project-task-note"><strong>Progress notes</strong><p class="preserve-lines">{{ $task->notes }}</p></div>@endif
                @if($task->evidence)<div class="project-task-note"><strong>Completion evidence</strong><p class="preserve-lines">{{ $task->evidence }}</p></div>@endif
                @if($canUpdateTask)<details class="task-edit-panel no-print"><summary>Update task</summary>@include('chapter.partials.task-form')</details>@endif
            </article>
        @empty
            <p class="panel-empty">No tasks yet. The assigned Chair can create tasks after project approval.</p>
        @endforelse
    </div>
    @if($manages)<details class="task-edit-panel task-create-panel no-print"><summary>Create a task</summary>@include('chapter.partials.task-form',['task'=>new \App\Models\Task])</details>@endif
</section>
<section class="panel form-panel" id="timeline"><h2>Timeline & activities</h2>@forelse($project->events->sortBy('starts_on') as $event)<div class="list-row"><strong>{{ $event->title }}</strong><span>{{ $event->type }} · {{ $event->starts_on?->format('M d, Y') }} · {{ $event->venue }}</span></div>@empty<p class="panel-empty">No activities or milestones scheduled.</p>@endforelse
@if($manages)<details class="record-detail no-print"><summary>＋ Schedule activity / milestone</summary><form method="POST" action="{{ route('calendar.store') }}" class="form-grid">@csrf<input type="hidden" name="project_id" value="{{ $project->id }}"><x-field name="title" label="Activity title" required/><x-field name="type" label="Activity type" type="select" :options="array_combine(['Activity','Milestone','Meeting','Review'],['Activity','Milestone','Meeting','Review'])" required/><x-field name="starts_on" label="Activity start date" type="date" required/><x-field name="ends_on" label="Activity end date" type="date"/><x-field name="venue" label="Activity venue"/><x-field name="description" label="Activity details" type="textarea"/><div class="full"><button class="btn primary">Save activity</button></div></form></details>@endif</section>
<section class="panel form-panel" id="budget"><h2>Budget & funds</h2><div class="table-wrap"><table><thead><tr><th>Category</th><th>Approved allocation</th><th>Approving authority</th><th>Date</th></tr></thead><tbody>@forelse($project->allocations as $allocation)<tr><td>{{ $allocation->category }}</td><td>₱{{ number_format($allocation->amount,2) }}</td><td>{{ $allocation->approved_by }}</td><td>{{ $allocation->approved_on?->format('M d, Y') }}</td></tr>@empty<tr><td colspan="4">No budget allocations recorded.</td></tr>@endforelse</tbody></table></div>
<p class="hint">Unallocated proposed budget: ₱{{ number_format($project->proposed_budget-$project->allocated,2) }}</p>
@if($u->role==='treasurer' && in_array($project->status,['Approved','Ongoing']))<details class="record-detail no-print"><summary>Record / adjust budget allocation</summary><form method="POST" action="{{ route('budget.store',$project) }}" class="form-grid">@csrf<x-field name="category" label="Budget category" required/><x-field name="amount" label="Approved allocation (PHP)" type="number" step="0.01" min="0" required/><x-field name="approved_by" label="Approving authority" required/><x-field name="approved_on" label="Approval date" type="date" required/><x-field name="remarks" label="Allocation remarks / adjustment reason" type="textarea" required/><div class="full"><p class="hint">Use an existing category name to adjust its allocation. Every change is recorded.</p><button class="btn primary">Save allocation</button></div></form></details>@endif
<h3 class="subheading">Project transactions</h3><div class="table-wrap"><table><thead><tr><th>Reference / date</th><th>Description</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead><tbody>@forelse($project->transactions as $entry)<tr><td>{{ $entry->reference }}<small>{{ $entry->transaction_date?->format('M d, Y') }}</small></td><td>{{ $entry->description }}</td><td>{{ $entry->type }}</td><td>₱{{ number_format($entry->amount,2) }}</td><td><span class="badge">{{ $entry->status }}</span></td></tr>@empty<tr><td colspan="5">No project transactions recorded.</td></tr>@endforelse</tbody></table></div></section>
<section class="panel form-panel" id="documents"><h2>Project documents</h2>@forelse($project->documents as $document)<a class="list-row" href="{{ route('documents.download',$document) }}"><span><strong>{{ $document->title }}</strong><small>{{ $document->category }} · {{ $document->original_name }}</small></span><span class="text-link">Download ↓</span></a>@empty<p class="panel-empty">Supporting documents, task evidence, and project documentation appear here.</p>@endforelse
@if($manages||$project->canEdit($u))<details class="record-detail no-print"><summary>＋ Upload document</summary><form method="POST" action="{{ route('documents.store',$project) }}" enctype="multipart/form-data" class="form-grid">@csrf<x-field name="title" label="Document title" required/><x-field name="category" label="Document category" value="Supporting document" required/><x-field name="file" label="File (PDF, Office, image, CSV or text; max 10 MB)" type="file" required/><div class="full"><button class="btn primary">Upload document</button></div></form></details>@endif</section>
<section class="panel form-panel" id="reports">
    <h2>JCI LOIs & project reports</h2>
    <div class="button-row no-print">
        @if($manages)
            <a class="btn secondary" href="{{ route('records.create', ['kind' => 'letters', 'project_id' => $project->id]) }}">Prepare JCI LOI</a>
            <a class="btn secondary" href="{{ route('records.create', ['kind' => 'reports', 'project_id' => $project->id]) }}">Prepare project report</a>
        @endif
    </div>
    @foreach(['letters' => $project->letters, 'reports' => $project->reports] as $kind => $records)
        @foreach($records as $record)
            @if($project->chair_id === $u->id || in_array($u->role, ['admin', 'bod']) || in_array($record->status, ['Reviewed', 'Approved for Sending', 'Sent', 'Archived']))
                <a class="list-row" href="{{ route('records.show', [$kind, $record->id]) }}">
                    <strong>{{ $record->title }}</strong>
                    <span>{{ $kind === 'letters' && $record->type === 'External Partner Letter' ? 'JCI LOI' : $record->type }} · v{{ $record->version }} · {{ $record->status }}</span>
                </a>
            @endif
        @endforeach
    @endforeach
</section>
@endsection

