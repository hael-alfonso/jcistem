@extends('layouts.chapter')
@section('title','Project workspace')
@section('content')
@php
    $u=auth()->user();
    $manages=$project->canManage($u);
    $collectionUrl = ($project->created_by === $u->id || $project->chair_id === $u->id)
        ? route('projects', ['filter' => 'mine'])
        : ($project->status === 'Completed'
        ? route($project->archived_at ? 'projects.archived' : 'projects.completed')
        : (in_array($project->status, ['Declined', 'Not Approved', 'Cancelled'], true)
            ? route('projects.archived', ['archive' => 'cancelled'])
            : route('projects')));
@endphp
<header class="project-workspace-hero">
    <div class="project-workspace-hero-main">
        <a class="back-link" href="{{ $collectionUrl }}"><x-icon name="arrowLeft" /> Back to Projects</a>
        <div class="project-workspace-reference"><span>{{ $project->reference }}</span><span>{{ $project->area }}</span></div>
        <h1>{{ $project->title }}</h1>
        <div class="project-workspace-meta">
            <span><small>Proposed by</small><strong>{{ $project->owner?->name ?? 'Not assigned' }}</strong></span>
            <span><small>Schedule</small><strong>{{ $project->starts_on?->format('M d, Y') ?? 'To be set' }}@if($project->ends_on) – {{ $project->ends_on->format('M d, Y') }}@endif</strong></span>
            <span><small>Venue</small><strong>{{ $project->venue ?? 'To be set' }}</strong></span>
        </div>
    </div>
    <div class="project-workspace-hero-actions"><span class="badge large project-status status-{{ $project->status_tone }}">{{ \App\Support\ProjectFlow::statusLabel($project) }}</span>@if($project->archived_at)<span class="badge">Archived record</span>@endif @if(in_array($project->status, ['Completed', 'Archived'], true))<form method="POST" action="{{ route('projects.reuse', $project) }}">@csrf<button class="btn primary" type="submit" data-confirm="Create a new editable concept from this stored project? Review its plan and budget before resubmitting."><x-icon name="filePlus" /> Reuse as new concept</button></form>@endif<button class="btn secondary" type="button" data-print><x-icon name="print" /> Print / save PDF</button></div>
</header>
@if(($project->created_by === $u->id || $project->chair_id === $u->id) && in_array($project->status, ['Needs Revision', 'Returned for Revision', 'Cancelled', 'Declined', 'Not Approved'], true))
<div class="alert {{ in_array($project->status, ['Needs Revision', 'Returned for Revision'], true) ? 'warning' : 'error' }} project-owner-status-alert" role="status"><strong>{{ \App\Support\ProjectFlow::statusLabel($project) }}.</strong> {{ in_array($project->status, ['Needs Revision', 'Returned for Revision'], true) ? 'Review the latest comments below, update your project, and resubmit when ready.' : 'This project is closed. Review the decision and retained project records below.' }}</div>
@endif
@php
    $trackedTasks = $project->tasks;
    $trackedTasks->each(fn ($task) => $task->setRelation('project', $project));
    $completedTasks = $trackedTasks->where('status', 'Completed')->count();
    $nextTask = $trackedTasks->where('status', '!=', 'Completed')->sortBy(fn ($task) => $task->deadline?->format('Y-m-d') ?? '9999-12-31')->first();
    $teamMemberIds = $trackedTasks->flatMap(fn ($task) => $task->assignees ?? [])->unique()->reject(fn ($id) => $id === $project->chair_id);
    $projectTeam = $members->whereIn('id', $teamMemberIds);
    $supportingDocuments = $project->documents->whereNull('submission_stage');
    $evidenceDocuments = $project->documents->whereIn('submission_stage', ['attendance', 'evaluation'])->sortByDesc('activity_on');
    $canAddEvidence = !$project->archived_at && $project->isChair($u) && in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review', 'Completed'], true);
    $partnershipDocuments = $project->documents->where('submission_stage', 'partnership')->sortByDesc('version');
    $canUploadPartnership = $u->status === 'active' && !$project->archived_at && $project->status !== 'Cancelled' && ($u->role === 'admin' || $project->created_by === $u->id || $project->isChair($u));
    $cancellation = $project->status === 'Cancelled' ? $project->reviews->where('action', 'cancel')->sortByDesc('id')->first() : null;
    $appeal = $cancellation ? $project->reviews->where('action', 'appeal_submitted')->where('id', '>', $cancellation->id)->sortByDesc('id')->first() : null;
    $appealDecision = $appeal ? $project->reviews->whereIn('action', ['appeal_approved', 'appeal_declined'])->where('id', '>', $appeal->id)->sortByDesc('id')->first() : null;
    $canSeeAppeal = $u->role === 'admin' || $project->created_by === $u->id || $project->chair_id === $u->id;
    $canViewProjectFiles = \App\Services\ChapterService::canViewProjectFiles($project, $u);
    $flowNext = \App\Support\ProjectFlow::next($project);
    $tracker = \App\Support\ProjectFlow::tracker($project, $completedTasks, $trackedTasks->count());
    $closedRecord = in_array($project->status, ['Completed', 'Archived', 'Cancelled', 'Declined', 'Not Approved'], true);
@endphp
@if($project->status === 'Cancelled')
<section class="panel project-cancellation-card" id="cancellation-decision" aria-labelledby="cancellation-decision-title">
    <div class="project-cancellation-card-copy">
        <span class="eyebrow">PROJECT DECISION</span>
        <h2 id="cancellation-decision-title">Project cancelled</h2>
        <p>{{ $cancellation?->comments ? \Illuminate\Support\Str::limit($cancellation->comments, 170) : 'This project was closed. The cancellation record is available in the project history.' }}</p>
        <small>{{ $cancellation?->created_at?->format('M d, Y') ?? 'Date not recorded' }}@if($cancellation?->user) · {{ $cancellation->user->name }}@endif</small>
    </div>
    <div class="project-cancellation-card-actions no-print">
        @if($canSeeAppeal && $appeal && !$appealDecision)<span class="badge status-review">Appeal awaiting Admin review</span>@elseif($canSeeAppeal && $appealDecision?->action === 'appeal_declined')<span class="badge status-declined">Appeal declined</span>@endif
        <button class="btn secondary" type="button" data-project-dialog-open="project-cancellation-dialog" aria-haspopup="dialog" aria-controls="project-cancellation-dialog">View reason{{ $cancellation && $canSeeAppeal && $u->role !== 'admin' && !$appeal ? ' & appeal' : '' }}</button>
    </div>
</section>
<dialog class="task-modal project-decision-dialog no-print" id="project-cancellation-dialog" aria-labelledby="project-cancellation-dialog-title">
    <div class="task-modal-header"><div><span class="eyebrow">PROJECT DECISION</span><h2 id="project-cancellation-dialog-title">Why this project was cancelled</h2><p>{{ $project->title }}</p></div><button class="task-modal-close" type="button" data-project-dialog-close aria-label="Close cancellation details"><x-icon name="close" /></button></div>
    <div class="task-modal-body">
        @if(old('_project_dialog') === 'project-cancellation-dialog' && $errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
        <div class="project-decision-reason"><strong>Recorded reason</strong><p>{{ $cancellation?->comments ?: 'No reason was recorded for this older project.' }}</p><small>{{ $cancellation?->created_at?->format('M d, Y') ?? 'Date not recorded' }}@if($cancellation?->user) · Recorded by {{ $cancellation->user->name }}@endif</small></div>
        @if($canSeeAppeal && $appeal)
            <div class="project-appeal-record"><strong>Appeal from {{ $appeal->user?->name ?? 'project owner' }}</strong><p>{{ $appeal->comments }}</p><small>Submitted {{ $appeal->created_at?->format('M d, Y') }}</small></div>
            @if($appealDecision)<div class="project-appeal-record"><strong>{{ $appealDecision->action === 'appeal_approved' ? 'Appeal approved' : 'Appeal declined' }}</strong><p>{{ $appealDecision->comments }}</p></div>@endif
        @elseif($cancellation && ($project->created_by === $u->id || $project->chair_id === $u->id))
            <form method="POST" action="{{ route('projects.appeal', $project) }}" class="project-appeal-form">@csrf<input type="hidden" name="_project_dialog" value="project-cancellation-dialog"><h3>Request a review</h3><p>Explain what changed or why the cancellation should be reconsidered. Admin will review this request; the project stays closed until an appeal is approved.</p><label class="field"><span>Appeal message <em aria-hidden="true">*</em></span><textarea name="message" rows="4" minlength="20" maxlength="2000" required placeholder="Describe why this project should be reopened and what has changed">{{ old('message') }}</textarea></label><button class="btn primary" type="submit">Send appeal to Admin</button></form>
        @elseif(!$cancellation && ($project->created_by === $u->id || $project->chair_id === $u->id))
            <p class="hint">This older cancelled record has no saved decision or previous stage. Ask Admin to review the original record before it can be reopened safely.</p>
        @endif
        @if($u->role === 'admin' && $appeal && !$appealDecision)
            <form method="POST" action="{{ route('projects.appeal.decision', $project) }}" class="project-appeal-form">@csrf<input type="hidden" name="_project_dialog" value="project-cancellation-dialog"><h3>Review appeal</h3><p>Approving restores the project to its pre-cancellation stage. Existing financial and audit records remain unchanged; cancelled fund releases are not restored.</p><label class="field"><span>Decision and explanation <em aria-hidden="true">*</em></span><textarea name="comments" rows="3" minlength="10" maxlength="2000" required placeholder="Record the reason for your decision">{{ old('comments') }}</textarea></label><div class="button-row"><button class="btn primary" name="decision" value="approve" type="submit" data-confirm="Approve this appeal and reopen the project at its previous stage?">Approve & reopen</button><button class="btn danger" name="decision" value="decline" type="submit" data-confirm="Decline this appeal and keep the project closed?">Decline appeal</button></div></form>
        @endif
    </div>
    <div class="task-modal-actions"><button class="btn secondary" type="button" data-project-dialog-close>Close</button></div>
</dialog>
@endif
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
    @if($project->status === 'Cancelled')<p class="project-cancelled-note">New tasks, allocations, and expenses are closed. Earlier decisions and posted financial records remain available.</p>@endif
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
@if($project->canEdit($u))<a class="btn secondary" href="{{ route('projects.edit',$project) }}"><x-icon name="edit" /> Edit {{ in_array($project->status, ['Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision', 'Approved'], true) ? 'full proposal (PPF)' : 'concept' }}</a>@endif
@if($project->created_by === $u->id && in_array($project->status,['Draft Concept','Needs Revision']))
@php $conceptIssues = \App\Support\ProjectSubmission::issues($project, 'concept'); @endphp
@if($conceptIssues)<div class="project-submit-readiness"><strong>Finish the concept before submitting</strong><ul>@foreach(array_slice(array_values($conceptIssues), 0, 3) as $issue)<li>{{ $issue }}</li>@endforeach</ul>@if(count($conceptIssues) > 3)<small>And {{ count($conceptIssues) - 3 }} more required {{ \Illuminate\Support\Str::plural('detail', count($conceptIssues) - 3) }}.</small>@endif</div>
@else @include('chapter.partials.project-action',['action'=>'submit_concept','label'=>'Submit for Admin Review','tone'=>'primary']) @endif
@endif
@if($u->role === 'admin' && in_array($project->status,['Admin Review','Submitted for President Review']))
@if($project->created_by === $u->id)<p class="review-self-note"><x-icon name="info" /> You submitted this project. The permanent history will record you as both project owner and Admin reviewer.</p>@endif
<form method="POST" action="{{ route('projects.transition',$project) }}" class="project-review-actions-form">@csrf
    <x-field name="comments" label="Review comments / conditions" type="textarea" required/>
    <div class="button-row"><button class="btn primary" name="action" value="endorse">Send for Budget Review</button><button class="btn caution" name="action" value="revise_concept">Return for Revision</button></div>
</form>
@endif
@if($u->role === 'treasurer' && $project->created_by !== $u->id && $project->status === 'Treasurer Review')
<p class="hint">Review the concept budget. The Board's formal verdict is recorded after the complete proposal is submitted.</p>
<form method="POST" action="{{ route('projects.transition',$project) }}" class="project-review-actions-form">@csrf
    <x-field name="comments" label="Budget review comments / conditions" type="textarea" required/>
    <div class="button-row"><button class="btn primary" name="action" value="budget_review">Endorse for Full Proposal</button><button class="btn caution" name="action" value="revise_budget">Return for Revision</button></div>
</form>
@endif
@if($u->role === 'admin' && $project->status === 'BOD Agenda')
<p class="hint">This project was placed in the former concept-level Board queue. Concepts now move to full-proposal preparation; record the Board verdict after the complete proposal and budget are reviewed.</p>
<form method="POST" action="{{ route('projects.transition',$project) }}" class="project-review-actions-form">@csrf
<x-field name="comments" label="Routing note" type="textarea" required/>
<button class="btn primary" name="action" value="advance_concept">Continue to Full Proposal Preparation</button>
</form>
@endif
@if($project->isChair($u) && in_array($project->status, ['Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision'], true) && !$project->proposal_reviewed_at)
<p class="hint">The concept passed Admin and Treasurer review. Complete the full PPF, then submit it for independent budget verification and the final BOD verdict.</p>
@php $proposalIssues = \App\Support\ProjectSubmission::issues($project, 'proposal'); @endphp
@if($proposalIssues)<div class="project-submit-readiness"><strong>Finish the full proposal before submitting</strong><ul>@foreach(array_slice(array_values($proposalIssues), 0, 3) as $issue)<li>{{ $issue }}</li>@endforeach</ul>@if(count($proposalIssues) > 3)<small>And {{ count($proposalIssues) - 3 }} more required {{ \Illuminate\Support\Str::plural('detail', count($proposalIssues) - 3) }}.</small>@endif</div>
@else @include('chapter.partials.project-action',['action'=>'submit_full_proposal','label'=>'Submit Proposal for Review','tone'=>'primary']) @endif
@endif
@if($project->status === 'Submitted for Formal Approval' && $u->role === 'treasurer' && $project->created_by !== $u->id && $project->chair_id !== $u->id)
@if(!$project->proposal_budget_reviewed_at)<p class="hint">Verify the full proposal's itemized budget before Admin records the signed Board verdict.</p>@else<p class="hint">You verified this proposal revision. It is awaiting the Admin's signed Board verdict.</p>@endif
<form method="POST" action="{{ route('projects.transition',$project) }}" class="project-review-actions-form">@csrf
    <x-field name="comments" label="Review comments / conditions" type="textarea" required/>
    <div class="button-row">@if(!$project->proposal_budget_reviewed_at)<button class="btn primary" name="action" value="review_full_budget">Verify Proposal Budget</button>@endif<button class="btn caution" name="action" value="return_full_proposal">Return Proposal for Revision</button></div>
</form>
@endif
@if($project->status === 'Submitted for Formal Approval' && $u->role === 'bod')
<p class="project-board-readonly-note"><strong>Board review copy.</strong> Read the submitted full proposal and budget before the in-person meeting. The Treasurer verifies the budget; after the meeting, Admin records the signed Board decision. There is no online BOD approval button.</p>
@endif
@if($project->status === 'Submitted for Formal Approval' && $u->role === 'admin')
@if($project->proposal_budget_reviewed_at)
<p class="hint">Record the final Board decision only after the Treasurer verifies this exact proposal version.</p>
<form method="POST" action="{{ route('projects.transition',$project) }}" enctype="multipart/form-data" class="project-review-actions-form project-board-decision-form">@csrf
<div class="form-grid"><x-field name="meeting_date" label="Final Board meeting date" type="date" required/><x-field name="evidence" label="Signed Board decision evidence (PDF/JPG/PNG, max 10 MB)" type="file" required/></div>
<x-field name="comments" label="Official decision and conditions" type="textarea" required/>
<div class="button-row"><button class="btn primary" name="action" value="approve_full_proposal" data-confirm="Record the signed final Board approval for this full proposal?">Record Approval</button><button class="btn caution" name="action" value="defer_full_proposal" data-confirm="Record the signed Board deferral for this full proposal?">Record Deferral</button><button class="btn danger" name="action" value="reject_full_proposal" data-confirm="Record the signed Board rejection for this full proposal?">Record Rejection</button><button class="btn secondary" name="action" value="return_full_proposal" formnovalidate data-confirm="Return the full proposal for revision?">Return for Revision</button></div>
</form>
@else<p class="hint">Treasurer verification of the full proposal budget is required before final BOD approval.</p>@endif
@endif
@if($project->isChair($u) && $project->status === 'Approved' && $project->proposal_reviewed_at)@include('chapter.partials.project-action',['action'=>'lock_plan','label'=>'Finalize Approved Project Plan','tone'=>'primary'])@endif
@if($manages && $project->status === 'Plan Locked')@include('chapter.partials.project-action',['action'=>'start','label'=>'Start Implementation','tone'=>'primary'])@endif
@if($manages && $project->status === 'Ongoing')@include('chapter.partials.project-action',['action'=>'request_completion','label'=>'Finish Implementation and Prepare Reports'])@endif
@if($project->status === 'Completion Review')<p class="hint">The chair submits a completion report and liquidation. Independent Admin and Treasurer approvals complete the project; it moves to the archive seven days later.</p>@endif
</div>
@if($project->canCancel($u))
<div class="project-cancel-wrap"><section class="project-cancel-action" aria-labelledby="project-cancel-title">
    <div><strong id="project-cancel-title">Need to close this project?</strong><p>Cancellation stops new project work. The reason and all earlier records remain visible to the owner and reviewers.</p></div>
    <button class="btn danger" type="button" data-project-dialog-open="project-cancel-dialog" aria-haspopup="dialog" aria-controls="project-cancel-dialog">Cancel project</button>
</section></div>
<dialog class="task-modal project-decision-dialog no-print" id="project-cancel-dialog" aria-labelledby="project-cancel-dialog-title"><div class="task-modal-header"><div><span class="eyebrow">PROJECT DECISION</span><h2 id="project-cancel-dialog-title">Cancel this project?</h2><p>{{ $project->title }}</p></div><button class="task-modal-close" type="button" data-project-dialog-close aria-label="Close cancellation form"><x-icon name="close" /></button></div><form method="POST" action="{{ route('projects.transition', $project) }}">@csrf<input type="hidden" name="_project_dialog" value="project-cancel-dialog"><div class="task-modal-body">@if(old('_project_dialog') === 'project-cancel-dialog' && $errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif<p>New tasks, allocations, and expenses will close. Earlier decisions, tasks, claims, and posted ledger records stay available. Pending claims still need a Treasurer decision.</p><label class="field"><span>Reason for cancellation <em aria-hidden="true">*</em></span><textarea name="comments" rows="4" minlength="15" maxlength="5000" required placeholder="Explain why this project cannot continue">{{ old('comments') }}</textarea></label><small class="hint">Give a specific reason (at least 15 characters); the owner will see it and may request a review.</small></div><div class="task-modal-actions"><button class="btn secondary" type="button" data-project-dialog-close>Keep project</button><button class="btn danger" type="submit" name="action" value="cancel" data-confirm="Cancel this project? Existing records remain available, but new work and spending will close.">Confirm cancellation</button></div></form></dialog>
@endif
@if(old('_project_dialog'))<span hidden data-project-dialog-reopen="{{ old('_project_dialog') }}"></span>@endif
<p class="hint">Monitoring access does not grant editing, approval, or financial recording authority.</p></section>
@endif
@if($project->decisions->isNotEmpty())
<section class="panel form-panel" aria-labelledby="project-decisions-title">
    <div class="panel-heading flush"><div><h2 id="project-decisions-title">Board decision history</h2><p>{{ $project->decisions->count() }} recorded {{ \Illuminate\Support\Str::plural('decision', $project->decisions->count()) }}</p></div></div>
    <div class="project-decision-list">@foreach($project->decisions as $decision)<div class="list-row"><span><strong>{{ $decision->stage === 'proposal' ? 'Full proposal' : 'Concept' }} · {{ ucfirst($decision->decision) }}</strong><small>{{ $decision->meeting_date->format('d M Y') }} · {{ $decision->comments }}</small></span>@if(in_array($u->role,['admin','bod','treasurer']) || $project->isChair($u))<a class="text-link" href="{{ route('decisions.evidence',$decision) }}">Signed evidence</a>@endif</div>@endforeach</div>
</section>
@endif
@if($u->role === 'admin' || $project->created_by === $u->id || $project->chair_id === $u->id)
@php $cancellationHistory = $project->reviews->whereIn('action', ['cancel', 'appeal_submitted', 'appeal_approved', 'appeal_declined'])->sortBy('id'); @endphp
@if($cancellationHistory->isNotEmpty() && $project->status !== 'Cancelled')
<section class="panel form-panel project-cancellation-history" id="project-cancellation-history" aria-labelledby="project-cancellation-history-title"><div class="panel-heading flush"><div><h2 id="project-cancellation-history-title">Cancellation &amp; appeal history</h2><p>The original decision and appeal remain on record after reopening.</p></div></div><div class="project-decision-list">@foreach($cancellationHistory as $entry)<div class="list-row"><span><strong>{{ match($entry->action) { 'cancel' => 'Cancelled', 'appeal_submitted' => 'Appeal submitted', 'appeal_approved' => 'Appeal approved · reopened', default => 'Appeal declined' } }}</strong><small>{{ $entry->created_at?->format('M d, Y') }} · {{ $entry->user?->name ?? 'Former member' }}</small><p>{{ $entry->comments }}</p></span></div>@endforeach</div></section>
@endif
@endif
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
                $hasDependentTask = $trackedTasks->contains(fn ($other) => $other->id !== $task->id && in_array($task->id, array_map('intval', $other->dependencies ?? []), true));
                $canRemoveTask = $u->role === 'admin' && $project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true) && !$hasDependentTask;
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
                    <section class="task-decline-panel no-print" aria-labelledby="task-decline-title-{{ $task->id }}"><div class="project-record-heading"><div><strong id="task-decline-title-{{ $task->id }}">Decline assignment</strong><small>Send the Project Chair a clear reason.</small></div></div><form method="POST" action="{{ route('tasks.respond',[$project,$task]) }}">@csrf<input type="hidden" name="response" value="declined"><label class="field"><span>Reason for the Chair</span><textarea name="note" rows="3" maxlength="2000" required></textarea></label><button class="btn danger" type="submit">Send decline</button></form></section>
                    @elseif($canCompleteTask)
                    <form method="POST" action="{{ route('tasks.complete',[$project,$task]) }}" class="task-completion-form no-print">@csrf <input type="hidden" name="_task_dialog" value="task-detail-{{ $task->id }}"><div><span class="eyebrow">READY TO FINISH</span><h3>Mark this task complete</h3><p>Describe the finished result or reference a document in this project. Your note becomes the completion evidence.</p></div><label class="field"><span>Completion evidence <em aria-hidden="true">*</em></span><textarea name="evidence" rows="3" maxlength="10000" required placeholder="Example: Final volunteer roster uploaded to project documents">{{ old('_task_dialog') === 'task-detail-'.$task->id ? old('evidence', $task->evidence) : $task->evidence }}</textarea></label><button class="btn primary" type="submit"><x-icon name="check" /> Mark complete</button></form>
                    @elseif($myResponse === 'accepted' && $unmetDependencies->isNotEmpty())<p class="task-waiting-note">Complete prerequisite tasks first: {{ $unmetDependencies->pluck('title')->join(', ') }}.</p>@endif
                @endif
                @if($canUpdateTask)<section class="task-edit-panel no-print" aria-labelledby="task-edit-title-{{ $task->id }}"><div class="project-record-heading"><div><strong id="task-edit-title-{{ $task->id }}">Update task</strong><small>Keep the deliverable, owner, schedule, and dependencies current.</small></div><x-icon name="edit" /></div>@include('chapter.partials.task-form')</section>@endif
                </div>
                <div class="task-modal-actions">@if($canRemoveTask)<form method="POST" action="{{ route('tasks.destroy', [$project, $task]) }}">@csrf @method('DELETE')<button class="btn danger" type="submit" data-confirm="Remove this task? It will disappear from active work and project progress, but remain in the audit history and can be restored while the project is active."><x-icon name="trash" /> Remove task</button></form>@elseif($u->role === 'admin' && $hasDependentTask)<small class="task-remove-hint">Remove this task from other tasks' prerequisites first.</small>@endif<button class="btn secondary" type="button" data-task-dialog-close>Close</button></div>
                </dialog>
            </article>
        @empty
            <p class="panel-empty">No tasks yet. The Chair can create and assign tasks after locking the approved plan. Launch the project to begin work.</p>
        @endforelse
    </div>
    @if($u->role === 'admin' && $removedTasks->isNotEmpty())
    <section class="task-removed-section no-print" aria-labelledby="removed-tasks-title"><div><h3 id="removed-tasks-title">Removed tasks</h3><p>Kept for audit history. Restore is available while this project is active.</p></div>
        @foreach($removedTasks as $removedTask)<div class="task-removed-row"><span><strong>{{ $removedTask->title }}</strong><small>Removed {{ $removedTask->deleted_at?->format('M d, Y') }}</small></span>@if($project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true))<form method="POST" action="{{ route('tasks.restore', [$project, $removedTask->id]) }}">@csrf<button class="btn secondary" type="submit" data-confirm="Restore this task to active project work and progress?"><x-icon name="refresh" /> Restore task</button></form>@else<small>History locked</small>@endif</div>@endforeach
    </section>
    @endif
</section>
@if($manages)<dialog class="task-modal task-create-modal no-print" id="task-create-{{ $project->id }}" aria-labelledby="task-create-title-{{ $project->id }}"><div class="task-modal-header"><div><span class="eyebrow">{{ $project->title }}</span><h2 id="task-create-title-{{ $project->id }}">Create task</h2><p>Set a clear deliverable, owner, deadline, and priority.</p></div><button class="task-modal-close" type="button" data-task-dialog-close aria-label="Close create task form"><x-icon name="close" /></button></div><div class="task-modal-body">@if(old('_task_dialog') === 'task-create-'.$project->id && $errors->any())<div class="alert error" role="alert"><strong>Check the task details:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @include('chapter.partials.task-form',['task'=>new \App\Models\Task])</div></dialog>@endif
@if(old('_task_dialog'))<span hidden data-task-reopen="{{ old('_task_dialog') }}"></span>@endif
<section class="panel form-panel" id="schedule"><h2>Schedule &amp; activities</h2>@forelse($project->events->sortBy('starts_on') as $event)<div class="list-row"><strong>{{ $event->title }}</strong><span>{{ $event->type }} · {{ $event->starts_on?->format('M d, Y') }} · {{ $event->venue }}</span></div>@empty<p class="panel-empty">No activities or milestones scheduled.</p>@endforelse
@if($manages)<section class="record-detail no-print" aria-labelledby="schedule-activity-title"><div class="project-record-heading"><div><strong id="schedule-activity-title">Schedule activity or milestone</strong><small>Add a dated project activity, meeting, review, or milestone.</small></div><x-icon name="calendar" /></div><form method="POST" action="{{ route('calendar.store') }}" class="form-grid">@csrf<input type="hidden" name="project_id" value="{{ $project->id }}"><x-field name="title" label="Activity title" required/><x-field name="type" label="Activity type" type="select" :options="array_combine(['Activity','Milestone','Meeting','Review'],['Activity','Milestone','Meeting','Review'])" required/><x-field name="starts_on" label="Activity start date" type="date" required/><x-field name="ends_on" label="Activity end date" type="date"/><x-field name="venue" label="Activity venue"/><x-field name="description" label="Activity details" type="textarea"/><div class="full"><button class="btn primary"><x-icon name="save" /> Save activity</button></div></form></section>@endif</section>
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
  @if($manages||$project->canEdit($u))<section class="record-detail no-print" aria-labelledby="supporting-upload-title"><div class="project-record-heading"><div><strong id="supporting-upload-title">Upload supporting document</strong><small>Add implementation files, task evidence, and supporting records.</small></div></div><form method="POST" action="{{ route('documents.store',$project) }}" enctype="multipart/form-data" class="form-grid">@csrf<x-field name="title" label="Document title" required/><x-field name="category" label="Document category" value="Supporting document" required/><x-field name="file" label="PDF, Office, image, CSV, text, or short MP4 (max 10 MB)" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.pptx,.csv,.jpg,.jpeg,.png,.txt,.mp4" required/><div class="full"><button class="btn primary"><x-icon name="upload" /> Upload document</button></div></form></section>@endif</section>
<section class="panel form-panel project-evidence" id="evidence">
    <div class="panel-heading flush"><div><h2>Attendance &amp; evaluation</h2><p>Keep dated photos and project evaluations together throughout implementation.</p></div><a class="btn secondary no-print" href="{{ route('documents', ['type' => 'evidence']) }}"><x-icon name="folder" /> File library</a></div>
    @if($canAddEvidence)
    <div class="evidence-form-grid no-print">
        <section class="record-detail" aria-labelledby="attendance-evidence-title"><div class="project-record-heading"><div><strong id="attendance-evidence-title">Add attendance photos</strong><small>Record a dated activity with its attendance images.</small></div></div>
            <form method="POST" action="{{ route('projects.evidence.store', $project) }}" enctype="multipart/form-data" class="form-grid">@csrf<input type="hidden" name="type" value="attendance"><x-field name="title" label="Activity or attendance title" required/><x-field name="activity_on" label="Activity date" type="date" required/><label class="field full"><span>Attendance photos (up to {{ $evidencePhotoLimit }}, 10 MB each)</span><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></label><div class="full"><button class="btn primary"><x-icon name="upload" /> Save attendance photos</button></div></form>
        </section>
        <section class="record-detail" aria-labelledby="evaluation-evidence-title"><div class="project-record-heading"><div><strong id="evaluation-evidence-title">Add evaluation</strong><small>Document lessons, next steps, and optional images.</small></div><x-icon name="report" /></div>
            <form method="POST" action="{{ route('projects.evidence.store', $project) }}" enctype="multipart/form-data" class="form-grid">@csrf<input type="hidden" name="type" value="evaluation"><x-field name="title" label="Evaluation title" required/><x-field name="activity_on" label="Evaluation date" type="date" required/><label class="field full"><span>Written evaluation</span><textarea name="notes" rows="5" maxlength="5000" placeholder="What worked, what needs improvement, and the next step">{{ old('notes') }}</textarea></label><label class="field full"><span>Evaluation images (optional, up to {{ $evidencePhotoLimit }})</span><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label><div class="full"><button class="btn primary"><x-icon name="save" /> Save evaluation</button></div></form>
        </section>
    </div>
    @endif
    <div class="evidence-list">
        @forelse($evidenceDocuments as $document)
            <article class="evidence-item">
                @if($canViewProjectFiles && $document->path && $document->canPreviewInline())<a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" aria-label="View {{ $document->title }}"><img src="{{ route('documents.preview', $document) }}" alt="{{ $document->title }}" loading="lazy"></a>@endif
                <div>
                    <span class="eyebrow">{{ $document->submission_stage === 'attendance' ? 'ATTENDANCE' : 'EVALUATION' }} · {{ $document->activity_on?->format('d M Y') }}</span>
                    <h3>{{ $document->title }}</h3>
                    @if($document->notes)
                        <p class="preserve-lines">{{ $document->notes }}</p>
                    @endif
                    @if($document->path && $canViewProjectFiles)
                        <div class="button-row">
                            <a class="text-link" href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener"><x-icon name="eye" /> View image</a>
                            <a class="text-link" href="{{ route('documents.download', $document) }}"><x-icon name="download" /> Download original</a>
                        </div>
                    @elseif($document->path)<small>File available to the project team and reviewers until approval.</small>@endif
                </div>
            </article>
        @empty<p class="panel-empty">No attendance photos or evaluations yet.</p>@endforelse
    </div>
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
@if($u->role === 'admin' && $project->status === 'Completed' && !$project->archived_at)
<section class="panel form-panel no-print" aria-labelledby="project-archive-title">
    <h2 id="project-archive-title">Archive completed project</h2>
    <p>Completed projects move to the archive after seven days. Admin can move this one sooner without changing its Completed status.</p>
    <form method="POST" action="{{ route('projects.archive', $project) }}">@csrf<button class="btn secondary" type="submit" data-confirm="Archive this completed project now? Its status will remain Completed."><x-icon name="archive" /> Archive now</button></form>
</section>
@endif
@if($u->role === 'admin' && $project->archived_at && in_array($project->status, ['Completed', 'Cancelled', 'Declined', 'Not Approved'], true))
<section class="panel form-panel project-remove-panel no-print" aria-labelledby="project-remove-title">
    <h2 id="project-remove-title">Remove from project folders</h2>
    <p>This archived project will move to Deleted Projects. Its audit trail, claims, and posted ledger records remain stored. Admin can restore it to its original folder.</p>
    <form method="POST" action="{{ route('projects.delete', $project) }}" class="project-remove-form">@csrf @method('DELETE')
        <button class="btn danger" type="submit" data-confirm="Remove this closed project from project lists? Its financial and audit history will be retained.">Remove project</button>
    </form>
</section>
@endif
</div>
@endsection

