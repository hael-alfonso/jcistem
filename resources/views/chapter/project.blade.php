@extends('layouts.chapter')
@section('title','Project workspace')
@section('content')
@php
    $u=auth()->user();
    $manages=$project->canManage($u);
    $canReview=$u->role==='admin'||($u->role==='bod'&&$u->proposal_reviewer);
    $collectionRoute = match($project->status) {
        'Completed', 'Archived' => 'projects.completed',
        'Declined', 'Not Approved', 'Cancelled' => 'projects.cancelled',
        default => 'projects',
    };
@endphp
<header class="project-workspace-hero">
    <div class="project-workspace-hero-main">
        <a class="back-link" href="{{ route($collectionRoute) }}"><x-icon name="arrowLeft" /> Back to Projects</a>
        <div class="project-workspace-reference"><span>{{ $project->reference }}</span><span>{{ $project->area }}</span></div>
        <h1>{{ $project->title }}</h1>
        <div class="project-workspace-meta">
            <span><small>Proposed by</small><strong>{{ $project->owner?->name ?? 'Not assigned' }}</strong></span>
            <span><small>Schedule</small><strong>{{ $project->starts_on?->format('M d, Y') ?? 'To be set' }}@if($project->ends_on) – {{ $project->ends_on->format('M d, Y') }}@endif</strong></span>
            <span><small>Venue</small><strong>{{ $project->venue ?? 'To be set' }}</strong></span>
        </div>
    </div>
    <div class="project-workspace-hero-actions"><span class="badge large project-status status-{{ $project->status_tone }}">{{ \App\Support\ProjectFlow::statusLabel($project) }}</span>@if(in_array($project->status, ['Completed', 'Archived'], true))<form method="POST" action="{{ route('projects.reuse', $project) }}">@csrf<button class="btn primary" type="submit" data-confirm="Create a new editable concept from this stored project? Review its plan and budget before resubmitting."><x-icon name="filePlus" /> Reuse as new concept</button></form>@endif<button class="btn secondary" type="button" data-print><x-icon name="print" /> Print / save PDF</button></div>
</header>
@php
    $trackedTasks = $project->tasks;
    $trackedTasks->each(fn ($task) => $task->setRelation('project', $project));
    $completedTasks = $trackedTasks->where('status', 'Completed')->count();
    $nextTask = $trackedTasks->where('status', '!=', 'Completed')->sortBy(fn ($task) => $task->deadline?->format('Y-m-d') ?? '9999-12-31')->first();
    $teamMemberIds = $trackedTasks->flatMap(fn ($task) => $task->assignees ?? [])->unique()->reject(fn ($id) => $id === $project->chair_id);
    $projectTeam = $members->whereIn('id', $teamMemberIds);
    $supportingDocuments = $project->documents->whereNull('submission_stage');
    $evidenceDocuments = $project->documents->whereIn('submission_stage', ['attendance', 'evaluation'])->sortByDesc('activity_on');
    $canAddEvidence = $project->isChair($u) && in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review', 'Completed'], true);
    $partnershipDocuments = $project->documents->where('submission_stage', 'partnership')->sortByDesc('version');
    $canUploadPartnership = $u->status === 'active' && $project->status !== 'Cancelled' && ($u->role === 'admin' || $project->created_by === $u->id || $project->isChair($u));
    $cancellation = $project->status === 'Cancelled' ? $project->reviews->where('action', 'cancel')->sortByDesc('id')->first() : null;
    $flowNext = \App\Support\ProjectFlow::next($project);
    $tracker = \App\Support\ProjectFlow::tracker($project, $completedTasks, $trackedTasks->count());
    $closedRecord = in_array($project->status, ['Completed', 'Archived', 'Cancelled', 'Declined', 'Not Approved'], true);
@endphp
<section class="panel project-tracker" aria-labelledby="project-tracker-title">
    <div class="tracker-heading"><div><span class="eyebrow">PROJECT MANAGEMENT</span><h2 id="project-tracker-title">Project tracker</h2></div></div>
    <div class="tracker-details">
        <div>@include('chapter.partials.project-progress', ['totalTasks' => $trackedTasks->count()])<small class="hint">Approvals, completed tasks, the Project Completion Report (PCR), and the Liquidation Report contribute to overall progress. The date marker only shows schedule pace.</small></div>
        @if(in_array($project->status, ['Plan Locked', 'Ongoing'], true))
            <div><span>Next open task</span><strong>@if($nextTask)<a href="#task-{{ $nextTask->id }}">{{ $nextTask->title }}</a>@else No open task @endif</strong><small>{{ $nextTask?->deadline?->format('M d, Y') ?? ($project->status === 'Plan Locked' ? 'Create tasks before launching.' : 'All assigned tasks are complete.') }}</small></div>
        @elseif($closedRecord)
            <div><span>Task history</span><strong>{{ $completedTasks }} of {{ $trackedTasks->count() }} completed</strong><small>This record is closed to new task work.</small></div>
        @else
            <div><span>Task planning</span><strong>Follows final approval</strong><small>The Chair sets tasks after locking the approved plan.</small></div>
        @endif
        <div><span>Last updated</span><strong>{{ $project->updated_at?->format('M d, Y') ?? 'Not set' }}</strong><small>Project details and task status</small></div>
    </div>
    @if($project->status === 'Cancelled')
        <p class="project-cancelled-note"><strong>This project was cancelled.</strong> New tasks, allocations, and expenses are closed. Earlier decisions and posted financial records remain available.@if($cancellation?->comments) <span>Reason: {{ $cancellation->comments }}</span>@endif</p>
    @endif
    <div class="project-flow-next"><span class="project-flow-next-icon"><x-icon name="arrow" /></span><div><small>{{ $closedRecord ? 'PROJECT RECORD' : 'WHAT HAPPENS NEXT · '.$flowNext['actor'] }}</small><strong>{{ $flowNext['title'] }}</strong><p>{{ $flowNext['detail'] }}</p></div><a class="text-link" href="#{{ $flowNext['anchor'] }}">{{ $closedRecord ? 'Open record' : 'Open step' }} <x-icon name="arrow" /></a></div>
    <div class="project-journey" aria-labelledby="project-process-title">
        <div class="project-journey-heading"><div><strong id="project-process-title">Project Process</strong><small>Follow the project from idea to completion.</small></div><small>{{ $tracker['position'] }}</small></div>
        <ol class="project-stage-list" aria-label="Project process">
            @foreach($tracker['steps'] as $stage)
                @php $stageLabel = match($stage['state']) { 'done' => 'Done', 'current' => 'Current', 'finished' => 'Finished', 'stopped' => 'Stopped', 'next' => 'Next', default => 'Later' }; @endphp
                <li class="is-{{ $stage['state'] }}">
                    <div class="project-stage-content" @if($stage['state'] === 'current') aria-current="step" @endif>
                        <span class="project-stage-number" aria-hidden="true">{{ $loop->iteration }}</span>
                        <span class="project-stage-copy"><strong>{{ $stage['title'] }}</strong><small>{{ $stage['detail'] }}</small></span>
                        <span class="project-stage-state">{{ $stageLabel }}</span>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
<div class="project-snapshot-grid is-compact" aria-label="Project at a glance"><div id="chair"><span>Project Chair</span>@include('chapter.partials.person-chip', ['person' => $project->chair, 'project' => $project])@if(!$project->chair)<small>Assigned after concept approval</small>@endif</div><div><span>Available project funds</span><strong>PHP {{ number_format($project->remaining,2) }}</strong><small>PHP {{ number_format($project->spent,2) }} posted from PHP {{ number_format($project->allocated,2) }} allocated</small></div></div>
@if($project->status !== 'Cancelled')
<section class="panel form-panel project-next-actions no-print" id="project-actions"><div class="panel-heading flush"><div><span class="eyebrow">YOUR ACTIONS</span><h2>Available actions</h2><p>Only actions allowed for your role and this project stage appear here.</p></div></div><div class="action-grid">
@if(($u->role === 'admin' || $project->created_by === $u->id) && !$project->decisions()->exists() && in_array($project->status,['Endorsed for Development','Full Proposal Draft','Returned for Revision','Submitted for Formal Approval','Approved'], true) && !$project->plan_locked_at && !$project->tasks->count() && !$project->allocations->count() && !$project->transactions->count() && !$project->expenseClaims->count() && !$project->fundReleases->count())
<p class="hint">This earlier project has no recorded BOD decision. Its saved records remain available. Submit it through the current review process before continuing project work.</p>
@include('chapter.partials.project-action',['action'=>'restart_review','label'=>'Submit Existing Project for Review','comments'=>true])
@endif
@if($project->canEdit($u))<a class="btn secondary" href="{{ route('projects.edit',$project) }}"><x-icon name="edit" /> Edit {{ $project->status === 'Approved' ? 'project plan (PPF)' : 'concept' }}</a>@endif
@if($project->created_by === $u->id && in_array($project->status,['Draft Concept','Needs Revision']))
@php $conceptIssues = \App\Support\ProjectSubmission::issues($project, 'concept'); @endphp
@if($conceptIssues)<div class="project-submit-readiness"><strong>Finish the concept before submitting</strong><ul>@foreach(array_slice(array_values($conceptIssues), 0, 3) as $issue)<li>{{ $issue }}</li>@endforeach</ul>@if(count($conceptIssues) > 3)<small>And {{ count($conceptIssues) - 3 }} more required {{ \Illuminate\Support\Str::plural('detail', count($conceptIssues) - 3) }}.</small>@endif</div>
@else @include('chapter.partials.project-action',['action'=>'submit_concept','label'=>'Submit for Admin Review','tone'=>'primary']) @endif
@endif
@if($u->role === 'admin' && in_array($project->status,['Admin Review','Submitted for President Review']))
@if($project->created_by === $u->id)<p class="review-self-note"><x-icon name="info" /> You submitted this project. The permanent history will record you as both project owner and Admin reviewer.</p>@endif
@foreach(['endorse'=>'Send for Budget Review','revise_concept'=>'Return for Revision'] as $action=>$label)@include('chapter.partials.project-action',['comments'=>true])@endforeach
@endif
@if($u->role === 'treasurer' && $project->created_by !== $u->id && $project->status === 'Treasurer Review')
@foreach(['budget_review'=>'Send for Board Review','revise_budget'=>'Return for Revision'] as $action=>$label)@include('chapter.partials.project-action',['comments'=>true])@endforeach
@endif
@if($u->role === 'admin' && $project->status === 'BOD Agenda')
<form method="POST" action="{{ route('projects.transition',$project) }}" enctype="multipart/form-data">@csrf
<x-field name="meeting_date" label="Board meeting date" type="date" required/>
<x-field name="evidence" label="Signed minutes / decision evidence" type="file" required/>
<x-field name="comments" label="Official decision and remarks" type="textarea" required/>
<button class="btn primary" name="action" value="approve" data-confirm="Approve this project with the signed Board decision? This decision will be recorded in project history.">Record Board Approval</button>
<button class="btn caution" name="action" value="defer" data-confirm="Defer this project? The reason will be recorded in project history.">Record Deferral</button>
<button class="btn danger" name="action" value="reject" data-confirm="Record this BOD rejection? The decision will be saved in project history.">Record Rejection</button>
</form>
@endif
@if($project->isChair($u) && $project->status === 'Approved' && !$project->proposal_reviewed_at)
<p class="hint">The concept is approved. Complete the full PPF, review its itemized budget, then submit it for independent financial and BOD review.</p>
@php $proposalIssues = \App\Support\ProjectSubmission::issues($project, 'proposal'); @endphp
@if($proposalIssues)<div class="project-submit-readiness"><strong>Finish the full proposal before submitting</strong><ul>@foreach(array_slice(array_values($proposalIssues), 0, 3) as $issue)<li>{{ $issue }}</li>@endforeach</ul>@if(count($proposalIssues) > 3)<small>And {{ count($proposalIssues) - 3 }} more required {{ \Illuminate\Support\Str::plural('detail', count($proposalIssues) - 3) }}.</small>@endif</div>
@else @include('chapter.partials.project-action',['action'=>'submit_full_proposal','label'=>'Submit Proposal for Review','tone'=>'primary']) @endif
@endif
@if($project->status === 'Submitted for Formal Approval' && $u->role === 'treasurer' && $project->created_by !== $u->id && $project->chair_id !== $u->id)
@if(!$project->proposal_budget_reviewed_at)@include('chapter.partials.project-action',['action'=>'review_full_budget','label'=>'Verify Proposal Budget','comments'=>true,'tone'=>'primary'])@else<p class="hint">You verified this proposal revision. It is awaiting the signed Board decision.</p>@endif
@include('chapter.partials.project-action',['action'=>'return_full_proposal','label'=>'Return Proposal for Revision','comments'=>true])
@endif
@if($project->status === 'Submitted for Formal Approval' && $u->role === 'admin')
@if($project->proposal_budget_reviewed_at)
<form method="POST" action="{{ route('projects.transition',$project) }}" enctype="multipart/form-data">@csrf
<x-field name="meeting_date" label="Final Board meeting date" type="date" required/>
<x-field name="evidence" label="Signed final approval evidence" type="file" required/>
<x-field name="comments" label="Formal decision and conditions" type="textarea" required/>
<button class="btn primary" name="action" value="approve_full_proposal" data-confirm="Record the signed final Board approval for this proposal?">Record Final Board Approval</button>
</form>
@else<p class="hint">Treasurer verification of the full proposal budget is required before final BOD approval.</p>@endif
@include('chapter.partials.project-action',['action'=>'return_full_proposal','label'=>'Return Proposal for Revision','comments'=>true])
@endif
@if($project->isChair($u) && $project->status === 'Approved' && $project->proposal_reviewed_at)@include('chapter.partials.project-action',['action'=>'lock_plan','label'=>'Finalize Approved Project Plan','tone'=>'primary'])@endif
@if($manages && $project->status === 'Plan Locked')@include('chapter.partials.project-action',['action'=>'start','label'=>'Start Implementation','tone'=>'primary'])@endif
@if($manages && $project->status === 'Ongoing')@include('chapter.partials.project-action',['action'=>'request_completion','label'=>'Finish Implementation and Prepare Reports'])@endif
@if($project->status === 'Completion Review')<p class="hint">The chair submits a completion report and liquidation. Independent Admin and Treasurer approvals archive the project automatically.</p>@endif
</div>
@if($project->canCancel($u))
<div class="project-cancel-wrap"><details class="project-cancel-action">
    <summary><span class="project-cancel-label"><x-icon name="info" /><span>Cancel project</span></span><x-icon name="chevron" class="project-cancel-chevron" /></summary>
    <form method="POST" action="{{ route('projects.transition', $project) }}">@csrf
        <p>Cancellation closes future project work. Existing decisions, tasks, claims, and ledger records stay in the history. Pending claims still need a Treasurer decision.</p>
        <label class="field"><span>Reason for cancellation <em aria-hidden="true">*</em></span><textarea name="comments" rows="3" maxlength="5000" required placeholder="Explain why the project can no longer continue"></textarea></label>
        <button class="btn danger" type="submit" name="action" value="cancel" data-confirm="Cancel this project? New work and spending will close, while existing records stay available.">Confirm cancellation</button>
    </form>
</details></div>
@endif
<p class="hint">Monitoring access does not grant editing, approval, or financial recording authority.</p></section>
@endif
@if($project->decisions->isNotEmpty())<details class="panel project-decision-history"><summary><span><strong>Board decision history</strong><small>{{ $project->decisions->count() }} recorded {{ \Illuminate\Support\Str::plural('decision', $project->decisions->count()) }}</small></span><x-icon name="chevron" /></summary><div class="project-decision-list">@foreach($project->decisions as $decision)<div class="list-row"><span><strong>{{ $decision->stage === 'proposal' ? 'Full proposal' : 'Concept' }} · {{ ucfirst($decision->decision) }}</strong><small>{{ $decision->meeting_date->format('d M Y') }} · {{ $decision->comments }}</small></span>@if(in_array($u->role,['admin','bod','treasurer']) || $project->isChair($u))<a class="text-link" href="{{ route('decisions.evidence',$decision) }}">Signed evidence</a>@endif</div>@endforeach</div></details>@endif
<nav class="anchor-tabs project-section-tabs no-print" aria-label="Project records by process area" data-project-pages><a href="#overview"><x-icon name="info" /> Overview</a><a href="#proposal"><x-icon name="fileSignature" /> Proposal</a><a href="#delivery"><x-icon name="checklist" /> Implementation</a><a href="#budget"><x-icon name="wallet" /> Finance</a><a href="#files"><x-icon name="folder" /> Reports &amp; Files</a></nav>
@include('chapter.partials.project-overview-summary')
@include('chapter.partials.project-proposal-summary')
<div class="project-page-group" id="delivery">
<section class="panel form-panel project-tasks-section" id="tasks">
    <div class="panel-heading flush"><div><span class="eyebrow">DELIVERY</span><h2>Tasks &amp; milestones</h2><p>{{ $completedTasks }} of {{ $trackedTasks->count() }} completed</p></div><div class="project-task-section-actions"><a class="btn secondary" href="{{ route('tasks', ['project' => $project->id, 'mine' => 0]) }}">Open task board</a>@if($manages)<button class="btn primary no-print" type="button" data-task-dialog-open="task-create-{{ $project->id }}"><x-icon name="plus" /> Create task</button>@endif</div></div>
    @if($trackedTasks->isNotEmpty())
    <div class="project-task-overview"><div><strong>Implementation progress</strong><span>{{ $completedTasks }} done · {{ $trackedTasks->count() - $completedTasks }} remaining</span></div><progress max="{{ $trackedTasks->count() }}" value="{{ $completedTasks }}" aria-label="{{ $completedTasks }} of {{ $trackedTasks->count() }} project tasks complete"></progress><p>Assigned members accept their tasks, then mark finished work complete with a short evidence note. Reports open after every task is complete.</p></div>
    @else<p class="hint">The Chair creates tasks after locking the approved plan. Members accept assignments and complete work with evidence after launch.</p>@endif
    @if($project->status === 'Plan Locked')<div class="notice">Planning is open. Members can accept assignments now; launch the project from Available actions to enable progress updates.</div>@endif
    <div class="project-task-list">
        @forelse($project->tasks as $task)
            @php
                $taskTone = match($task->status) { 'Completed' => 'done', 'In Progress' => 'working', 'Blocked' => 'blocked', default => 'todo' };
                $priorityTone = match($task->priority) { 'Urgent' => 'urgent', 'High' => 'high', 'Medium' => 'medium', 'Low' => 'low', default => 'unset' };
                $assignedNames = $members->whereIn('id', $task->assignees ?? [])->pluck('name')->join(', ');
                $isOverdue = $task->deadline && $task->deadline->lt(today()) && $task->status !== 'Completed';
                $canUpdateTask = $manages || (in_array($u->id, $task->assignees ?? [], true) && ($task->assignment_responses[$u->id]['response'] ?? null) === 'accepted' && $project->plan_locked_at && $project->status === 'Ongoing');
                $myAssignment = in_array($u->id, $task->assignees ?? [], true);
                $myResponse = $task->assignment_responses[$u->id]['response'] ?? null;
                $unmetDependencies = $trackedTasks->whereIn('id', $task->dependencies ?? [])->where('status', '!=', 'Completed');
                $canCompleteTask = $myAssignment && $myResponse === 'accepted' && $project->status === 'Ongoing' && $project->plan_locked_at && $task->status !== 'Completed' && $unmetDependencies->isEmpty() && $trackedTasks->whereIn('id', $task->dependencies ?? [])->count() === count($task->dependencies ?? []);
            @endphp
            <article class="project-task-card" id="task-{{ $task->id }}">
                <div class="project-task-heading"><div><span class="project-task-kicker">{{ $task->milestone ?: 'Project task' }} · {{ $task->priority ?: 'Normal' }} priority</span><h3>{{ $task->title }}</h3>@if($task->description)<p>{{ \Illuminate\Support\Str::limit($task->description, 135) }}</p>@endif</div><span class="badge task-status task-status-{{ $taskTone }}">{{ $task->status }}</span></div>
                <div class="project-task-highlights"><span><x-icon name="calendar" /> {{ $task->deadline?->format('M d, Y') ?? 'No deadline' }}@if($isOverdue)<strong class="danger-text"> · Overdue</strong>@endif</span><span><x-icon name="users" /> {{ $assignedNames ?: 'Unassigned' }}</span></div>
                <p class="project-task-next"><strong>Next:</strong> {{ $task->nextStep($u) }}</p>
                <div class="project-task-card-footer"><span>{{ $task->status === 'Completed' ? 'Finished' : ($myAssignment ? 'Your assignment' : 'Project work') }}</span><div class="project-task-card-actions">@if($canCompleteTask)<button class="btn primary no-print" type="button" data-task-dialog-open="task-detail-{{ $task->id }}"><x-icon name="check" /> Mark complete</button>@endif<button class="project-task-view no-print" type="button" data-task-dialog-open="task-detail-{{ $task->id }}" aria-label="View task details: {{ $task->title }}" aria-haspopup="dialog" aria-controls="task-detail-{{ $task->id }}" title="View task details"><x-icon name="eye" /></button></div></div>
                <dialog class="task-modal task-detail-modal" id="task-detail-{{ $task->id }}" aria-labelledby="task-detail-title-{{ $task->id }}">
                <div class="task-modal-header"><div><span class="eyebrow">{{ $project->title }}</span><h2 id="task-detail-title-{{ $task->id }}">{{ $task->title }}</h2><p>Task details and progress</p></div><button class="task-modal-close" type="button" data-task-dialog-close aria-label="Close task details"><x-icon name="close" /></button></div>
                <div class="task-modal-body">
                @if($task->description)<div class="task-preview-copy"><h3>Instructions and expected deliverable</h3><p>{{ $task->description }}</p></div>@endif
                <dl class="project-task-meta">
                    <div><dt>Assigned to</dt><dd>{{ $assignedNames ?: 'Unassigned' }}</dd></div>
                    <div><dt>Deadline</dt><dd>{{ $task->deadline?->format('M d, Y') ?? 'Not set' }}@if($isOverdue) <span class="danger-text">· Overdue</span>@endif</dd></div>
                    <div><dt>Priority</dt><dd><span class="task-priority priority-{{ $priorityTone }}">{{ $task->priority ?: 'Not set' }}</span></dd></div>
                    <div><dt>Milestone</dt><dd>{{ $task->milestone ?: 'None' }}</dd></div>
                </dl>
                <div class="project-task-note"><strong>Next step</strong><p>{{ $task->nextStep($u) }}</p></div>
                @if($task->dependencies)
                <div class="project-task-note"><strong>Prerequisite tasks</strong><ul>@foreach($trackedTasks->whereIn('id', $task->dependencies) as $prerequisite)<li><a href="#task-{{ $prerequisite->id }}">{{ $prerequisite->title }}</a> &middot; {{ $prerequisite->status }}</li>@endforeach</ul></div>
                @endif
                @if($manages && $task->assignees)<div class="project-task-note"><strong>Assignment responses</strong><ul>@foreach($task->assignees as $assigneeId)<li>{{ $members->firstWhere('id', $assigneeId)?->name ?? 'Former member' }} &middot; {{ ucfirst($task->assignment_responses[$assigneeId]['response'] ?? 'pending') }}@if(!empty($task->assignment_responses[$assigneeId]['note'])) &mdash; {{ $task->assignment_responses[$assigneeId]['note'] }}@endif</li>@endforeach</ul></div>@endif
                @if($task->notes)<div class="project-task-note"><strong>Progress notes</strong><p class="preserve-lines">{{ $task->notes }}</p></div>@endif
                @if($task->evidence)<div class="project-task-note"><strong>Completion evidence</strong><p class="preserve-lines">{{ $task->evidence }}</p></div>@endif
                @if(old('_task_dialog') === 'task-detail-'.$task->id && $errors->any())<div class="alert error" role="alert"><strong>Check the task details:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @if($myAssignment && in_array($project->status, ['Plan Locked','Ongoing'], true) && $task->status !== 'Completed')
                    @if($myResponse !== 'accepted')
                    <div class="task-assignment-action no-print"><div><strong>{{ $myResponse === 'declined' ? 'Assignment declined' : 'You are assigned to this task' }}</strong><p>Accept to update progress and record completion after the project launches.</p></div><form method="POST" action="{{ route('tasks.respond',[$project,$task]) }}">@csrf<input type="hidden" name="response" value="accepted"><button class="btn primary" type="submit"><x-icon name="check" /> Accept assignment</button></form></div>
                    <details class="task-decline-panel no-print"><summary>Need to decline?</summary><form method="POST" action="{{ route('tasks.respond',[$project,$task]) }}">@csrf<input type="hidden" name="response" value="declined"><label class="field"><span>Reason for the Chair</span><textarea name="note" rows="3" maxlength="2000" required></textarea></label><button class="btn secondary" type="submit">Send decline</button></form></details>
                    @elseif($canCompleteTask)
                    <form method="POST" action="{{ route('tasks.complete',[$project,$task]) }}" class="task-completion-form no-print">@csrf <input type="hidden" name="_task_dialog" value="task-detail-{{ $task->id }}"><div><span class="eyebrow">READY TO FINISH</span><h3>Mark this task complete</h3><p>Describe the finished result or reference a document in this project. Your note becomes the completion evidence.</p></div><label class="field"><span>Completion evidence <em aria-hidden="true">*</em></span><textarea name="evidence" rows="3" maxlength="10000" required placeholder="Example: Final volunteer roster uploaded to project documents">{{ old('_task_dialog') === 'task-detail-'.$task->id ? old('evidence', $task->evidence) : $task->evidence }}</textarea></label><button class="btn primary" type="submit"><x-icon name="check" /> Mark complete</button></form>
                    @elseif($myResponse === 'accepted' && $unmetDependencies->isNotEmpty())<p class="task-waiting-note">Complete prerequisite tasks first: {{ $unmetDependencies->pluck('title')->join(', ') }}.</p>@endif
                @endif
                @if($canUpdateTask)<details class="task-edit-panel no-print" @if(old('_task_dialog') === 'task-detail-'.$task->id) open @endif><summary><x-icon name="edit" /> Update task</summary>@include('chapter.partials.task-form')</details>@endif
                </div>
                <div class="task-modal-actions"><button class="btn secondary" type="button" data-task-dialog-close>Close</button></div>
                </dialog>
            </article>
        @empty
            <p class="panel-empty">No tasks yet. The Chair can create and assign tasks after locking the approved plan. Launch the project to begin work.</p>
        @endforelse
    </div>
</section>
@if($manages)<dialog class="task-modal task-create-modal no-print" id="task-create-{{ $project->id }}" aria-labelledby="task-create-title-{{ $project->id }}"><div class="task-modal-header"><div><span class="eyebrow">{{ $project->title }}</span><h2 id="task-create-title-{{ $project->id }}">Create task</h2><p>Set a clear deliverable, owner, deadline, and priority.</p></div><button class="task-modal-close" type="button" data-task-dialog-close aria-label="Close create task form"><x-icon name="close" /></button></div><div class="task-modal-body">@if(old('_task_dialog') === 'task-create-'.$project->id && $errors->any())<div class="alert error" role="alert"><strong>Check the task details:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @include('chapter.partials.task-form',['task'=>new \App\Models\Task])</div></dialog>@endif
@if(old('_task_dialog'))<span hidden data-task-reopen="{{ old('_task_dialog') }}"></span>@endif
<section class="panel form-panel" id="schedule"><h2>Schedule &amp; activities</h2>@forelse($project->events->sortBy('starts_on') as $event)<div class="list-row"><strong>{{ $event->title }}</strong><span>{{ $event->type }} · {{ $event->starts_on?->format('M d, Y') }} · {{ $event->venue }}</span></div>@empty<p class="panel-empty">No activities or milestones scheduled.</p>@endforelse
@if($manages)<details class="record-detail no-print"><summary><x-icon name="calendar" /> Schedule activity / milestone<x-icon name="chevron" class="disclosure-icon" /></summary><form method="POST" action="{{ route('calendar.store') }}" class="form-grid">@csrf<input type="hidden" name="project_id" value="{{ $project->id }}"><x-field name="title" label="Activity title" required/><x-field name="type" label="Activity type" type="select" :options="array_combine(['Activity','Milestone','Meeting','Review'],['Activity','Milestone','Meeting','Review'])" required/><x-field name="starts_on" label="Activity start date" type="date" required/><x-field name="ends_on" label="Activity end date" type="date"/><x-field name="venue" label="Activity venue"/><x-field name="description" label="Activity details" type="textarea"/><div class="full"><button class="btn primary"><x-icon name="save" /> Save activity</button></div></form></details>@endif</section>
<section class="panel form-panel" id="team"><h2>Project team</h2><div class="list-row"><span><strong>{{ $project->chair?->name ?? 'Project Chair not assigned' }}</strong><small>Project Chair</small></span></div>@forelse($projectTeam as $member)<div class="list-row"><span><strong>{{ $member->name }}</strong><small>Assigned project member</small></span></div>@empty<p class="panel-empty">No additional team members are assigned to project tasks yet.</p>@endforelse</section>
</div>
<section class="panel form-panel" id="budget"><div class="panel-heading flush"><div><span class="eyebrow">PROJECT FINANCE</span><h2>Budget &amp; funds</h2><p>See the approved plan, Treasurer allocations, pending claims, and posted spending together.</p></div></div>
@if($project->budget_items)
<h3 class="subheading">Itemized proposed expenses</h3><div class="table-wrap"><table><thead><tr><th>Category</th><th>Specific expense</th><th>Qty</th><th>Unit cost</th><th>Estimated amount</th></tr></thead><tbody>@foreach($project->budget_items as $item)<tr><td>{{ \App\Support\ProjectBudget::CATEGORIES[$item['category']] ?? ucfirst(str_replace('_', ' ', $item['category'] ?? 'Other')) }}</td><td>{{ $item['description'] }}</td><td>{{ rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.') }}</td><td>₱{{ number_format((float) $item['unit_cost'], 2) }}</td><td><strong>₱{{ number_format((float) $item['amount'], 2) }}</strong></td></tr>@endforeach</tbody><tfoot><tr><th colspan="4">Total proposed budget</th><th>₱{{ number_format((float) $project->proposed_budget, 2) }}</th></tr></tfoot></table></div>
@else<p class="panel-empty">This earlier project has only a total proposed budget. Edit the concept or plan to add its specific expense breakdown.</p>@endif
@include('chapter.partials.project-budget-allocation')
@include('chapter.partials.project-fund-releases')
@include('chapter.partials.project-budget-reconciliation')
@include('chapter.partials.project-expense-claims')
@if($u->role === 'treasurer' || $project->isChair($u))@if($manages)@include('chapter.partials.project-transaction')@endif<h3 class="subheading">Treasurer ledger records</h3><p class="hint">Only posted debit records count as project spending. Voided entries remain visible for audit history.</p><div class="table-wrap project-transaction-wrap"><table class="project-transaction-table"><thead><tr><th>Reference / date</th><th>Description</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead><tbody>@forelse($project->transactions->sortByDesc('id') as $entry)<tr><td data-label="Reference"><strong>{{ $entry->reference }}</strong><small>{{ $entry->transaction_date?->format('M d, Y') }}</small></td><td data-label="Description">{{ $entry->description }}</td><td data-label="Type">{{ $entry->type }}</td><td data-label="Amount">PHP {{ number_format($entry->amount,2) }}</td><td data-label="Status"><span class="badge">{{ $entry->status }}</span></td></tr>@empty<tr><td colspan="5">No ledger records for this project yet.</td></tr>@endforelse</tbody></table></div>@endif</section>
<div class="project-page-group" id="files">
<section class="panel form-panel" id="documents"><h2>Supporting documents</h2><p class="hint">Concept and proposal uploads stay in their matching sections above. This area holds implementation files, task evidence, and supporting records.</p>@forelse($supportingDocuments as $document)@include('chapter.partials.project-document-row', ['document' => $document])@empty<p class="panel-empty">No supporting documents have been uploaded yet.</p>@endforelse
  @if($manages||$project->canEdit($u))<details class="record-detail no-print"><summary><x-icon name="upload" /> Upload document<x-icon name="chevron" class="disclosure-icon" /></summary><form method="POST" action="{{ route('documents.store',$project) }}" enctype="multipart/form-data" class="form-grid">@csrf<x-field name="title" label="Document title" required/><x-field name="category" label="Document category" value="Supporting document" required/><x-field name="file" label="PDF, Office, image, CSV, text, or short MP4 (max 10 MB)" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.pptx,.csv,.jpg,.jpeg,.png,.txt,.mp4" required/><div class="full"><button class="btn primary"><x-icon name="upload" /> Upload document</button></div></form></details>@endif</section>
<section class="panel form-panel project-evidence" id="evidence">
    <div class="panel-heading flush"><div><h2>Attendance &amp; evaluation</h2><p>Keep dated photos and project evaluations together throughout implementation.</p></div><a class="btn secondary no-print" href="{{ route('documents', ['type' => 'evidence']) }}"><x-icon name="folder" /> File library</a></div>
    @if($canAddEvidence)
    <div class="evidence-form-grid no-print">
        <details class="record-detail"><summary><x-icon name="upload" /> Add attendance photos<x-icon name="chevron" class="disclosure-icon" /></summary>
            <form method="POST" action="{{ route('projects.evidence.store', $project) }}" enctype="multipart/form-data" class="form-grid">@csrf<input type="hidden" name="type" value="attendance"><x-field name="title" label="Activity or attendance title" required/><x-field name="activity_on" label="Activity date" type="date" required/><label class="field full"><span>Attendance photos (up to {{ $evidencePhotoLimit }}, 10 MB each)</span><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></label><div class="full"><button class="btn primary"><x-icon name="upload" /> Save attendance photos</button></div></form>
        </details>
        <details class="record-detail"><summary><x-icon name="report" /> Add evaluation<x-icon name="chevron" class="disclosure-icon" /></summary>
            <form method="POST" action="{{ route('projects.evidence.store', $project) }}" enctype="multipart/form-data" class="form-grid">@csrf<input type="hidden" name="type" value="evaluation"><x-field name="title" label="Evaluation title" required/><x-field name="activity_on" label="Evaluation date" type="date" required/><label class="field full"><span>Written evaluation</span><textarea name="notes" rows="5" maxlength="5000" placeholder="What worked, what needs improvement, and the next step">{{ old('notes') }}</textarea></label><label class="field full"><span>Evaluation images (optional, up to {{ $evidencePhotoLimit }})</span><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label><div class="full"><button class="btn primary"><x-icon name="save" /> Save evaluation</button></div></form>
        </details>
    </div>
    @endif
    <div class="evidence-list">
        @forelse($evidenceDocuments as $document)
            <article class="evidence-item">
                @if($document->path && $document->canPreviewInline())<a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" aria-label="View {{ $document->title }}"><img src="{{ route('documents.preview', $document) }}" alt="{{ $document->title }}" loading="lazy"></a>@endif
                <div>
                    <span class="eyebrow">{{ $document->submission_stage === 'attendance' ? 'ATTENDANCE' : 'EVALUATION' }} · {{ $document->activity_on?->format('d M Y') }}</span>
                    <h3>{{ $document->title }}</h3>
                    @if($document->notes)
                        <p class="preserve-lines">{{ $document->notes }}</p>
                    @endif
                    @if($document->path)
                        <div class="button-row">
                            <a class="text-link" href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener"><x-icon name="eye" /> View image</a>
                            <a class="text-link" href="{{ route('documents.download', $document) }}"><x-icon name="download" /> Download original</a>
                        </div>
                    @endif
                </div>
            </article>
        @empty<p class="panel-empty">No attendance photos or evaluations yet.</p>@endforelse
    </div>
</section>
<section class="panel form-panel partnership-documents" id="partnerships">
    <div class="panel-heading flush"><div><h2>External partnership letters</h2><p>Keep sponsor and partner correspondence with this project, separate from the internal concept letter.</p></div><div class="button-row no-print">@if($manages || $project->canReport($u))<a class="btn secondary" href="{{ route('records.create', ['kind' => 'letters', 'project_id' => $project->id]) }}"><x-icon name="filePlus" /> Prepare partner LOI</a>@endif<a class="btn secondary" href="{{ route('templates.reference.download', 'external-partnership-letter') }}"><x-icon name="download" /> Official partnership template</a></div></div>
    @forelse($partnershipDocuments as $document)
        @include('chapter.partials.project-document-row', ['document' => $document, 'label' => $document->title.' · Version '.$document->version, 'meta' => $document->original_name.' · Uploaded '.($document->submitted_at?->format('d M Y, g:i A') ?? $document->created_at?->format('d M Y, g:i A'))])
    @empty
        <p class="panel-empty">No external partnership letter has been uploaded for this project.</p>
    @endforelse
    @if($canUploadPartnership)
    <details class="record-detail no-print"><summary><x-icon name="upload" /> Upload completed partnership letter<x-icon name="chevron" class="disclosure-icon" /></summary><form method="POST" action="{{ route('projects.partnerships.store', $project) }}" enctype="multipart/form-data" class="form-grid">@csrf<x-field name="title" label="Partner or letter title" required placeholder="Example: Partnership proposal for ABC Foundation"/><x-field name="file" label="Completed DOC, DOCX, or PDF (max 10 MB)" type="file" accept=".doc,.docx,.pdf" required/><div class="full"><p class="hint">Uploading a revised letter creates a new version and keeps the previous file available for the project record.</p><button class="btn primary"><x-icon name="upload" /> Upload partnership letter</button></div></form></details>
    @endif
</section>
@if($project->documents->contains(fn ($document) => $document->canPreviewInline()))
<dialog class="document-preview-dialog no-print" id="document-preview-dialog" aria-labelledby="document-preview-title">
    <div class="document-preview-header"><div><span class="eyebrow">PRIVATE FILE PREVIEW · <span data-document-preview-type>FILE</span></span><h2 id="document-preview-title" data-document-preview-title>Project file</h2><p>PDFs, images, text, and short videos use the uploaded file. Office files use a private browser preview; download the original for exact formatting.</p></div><button type="button" data-document-preview-close aria-label="Close file preview"><x-icon name="close" /></button></div>
    <div class="document-preview-canvas"><iframe title="Project document preview" data-document-preview-frame></iframe><video title="Project video preview" data-document-preview-video controls playsinline preload="metadata" hidden></video></div>
    <div class="document-preview-actions"><button class="btn secondary" type="button" data-document-preview-close>Close</button><a class="btn secondary" href="#" data-document-preview-open target="_blank" rel="noopener"><x-icon name="eye" /> Full screen</a><a class="btn primary" href="#" data-document-preview-download><x-icon name="download" /> Download original</a></div>
</dialog>
@endif
<section class="panel form-panel" id="completion">
    <div class="panel-heading flush"><div><h2>Completion and liquidation</h2><p>Close the project with separate outcome and financial records tied to this project.</p></div></div>
    <div class="completion-workflow"><div><span>1</span><strong>Complete project tasks</strong><small>Finish assigned work and upload implementation evidence.</small></div><div><span>2</span><strong>Submit completion report</strong><small>Admin reviews results, beneficiaries, and project evidence.</small></div><div><span>3</span><strong>Submit liquidation</strong><small>Treasurer verifies posted expenses and receipts independently.</small></div></div>
    <div class="button-row no-print">
        @if($manages || $project->canReport($u))
            @if($project->canReport($u))<a class="btn primary" href="{{ route('records.create', ['kind' => 'reports', 'project_id' => $project->id, 'type' => 'Completion']) }}"><x-icon name="checklist" /> Project Completion Report</a><a class="btn secondary" href="{{ route('records.create', ['kind' => 'reports', 'project_id' => $project->id, 'type' => 'Liquidation']) }}"><x-icon name="receipt" /> Liquidation Report</a>@endif
        @endif
        @if($u->role === 'treasurer')<a class="btn secondary" href="{{ route('projects.liquidation', $project) }}"><x-icon name="wallet" /> Live ledger liquidation</a>@endif
    </div>
    @foreach(['letters' => $project->letters, 'reports' => $project->reports] as $kind => $records)
        @foreach($records as $record)
            @if($kind === 'reports' && $record->type === 'Liquidation'
                ? ($project->chair_id === $u->id || $u->role === 'treasurer')
                : ($project->chair_id === $u->id || in_array($u->role, ['admin', 'bod'], true) || in_array($record->status, ['Reviewed', 'Approved for Sending', 'Sent', 'Archived'], true)))
                <a class="list-row" href="{{ route('records.show', [$kind, $record->id]) }}">
                    <strong>{{ $record->title }}</strong>
                    <span>{{ $kind === 'letters' && $record->type === 'External Partner Letter' ? 'JCI LOI' : $record->type }} · v{{ $record->version }} · {{ $record->status }}</span>
                </a>
            @endif
        @endforeach
    @endforeach
</section>
@if($u->role === 'admin' && in_array($project->status, ['Completed', 'Archived', 'Cancelled', 'Declined', 'Not Approved'], true))
<section class="panel form-panel project-remove-panel no-print" aria-labelledby="project-remove-title">
    <h2 id="project-remove-title">Remove from project folders</h2>
    <p>This closed project will move to Removed Projects. Its audit trail, claims, and posted ledger records remain stored. Admin can restore it to its original folder.</p>
    <form method="POST" action="{{ route('projects.delete', $project) }}" class="project-remove-form">@csrf @method('DELETE')
        <label class="field"><span>Reason for removal <em aria-hidden="true">*</em></span><textarea name="reason" rows="2" maxlength="2000" required></textarea></label>
        <button class="btn danger" type="submit" data-confirm="Remove this closed project from project lists? Its financial and audit history will be retained.">Remove project</button>
    </form>
</section>
@endif
</div>
@endsection

