@php
    $manages = $project->canManage(auth()->user());
    $creating = !$task->exists;
    $selectedAssignees = collect(old('assignees', $task->assignees ?? []))->map(fn ($id) => (string) $id)->all();
@endphp
<form method="POST" action="{{ $task->exists?route('tasks.update',[$project,$task]):route('tasks.store',$project) }}" class="form-grid task-work-form">@csrf @if($task->exists) @method('PUT') @endif
<input type="hidden" name="_task_dialog" value="{{ $task->exists ? 'task-detail-'.$task->id : 'task-create-'.$project->id }}">
@if($manages)
<div class="full task-form-section-heading"><span>01</span><div><h3>Task and deliverable</h3><p>Use an action-based title and explain what a completed result looks like.</p></div></div>
<div class="full"><x-field name="title" label="Task title" :value="$task->title" required placeholder="Example: Prepare beneficiary registration list"/></div>
<div class="full"><x-field name="description" label="Instructions and expected deliverable" type="textarea" :value="$task->description" placeholder="Describe the work, expected output, and any useful instructions"/></div>
<x-field name="priority" label="Priority" type="select" :options="array_combine(['Low','Medium','High','Urgent'],['Low','Medium','High','Urgent'])" :value="$task->priority??'Medium'" required/>
<x-field name="milestone" label="Milestone (optional)" :value="$task->milestone" placeholder="Example: Pre-event preparation"/>
<div class="full task-form-section-heading"><span>02</span><div><h3>Schedule and dependencies</h3><p>Set a realistic working period and identify any task that must finish first.</p></div></div>
<x-field name="starts_on" label="Start date" type="date" :value="$task->starts_on?->format('Y-m-d')"/>
<x-field name="deadline" label="Deadline" type="date" :value="$task->deadline?->format('Y-m-d')" required/>
<div class="full"><x-field name="dependencies" label="Prerequisite tasks (optional)" type="select" :options="$project->tasks->where('id','!=',$task->id)->pluck('title','id')->all()" :value="$task->dependencies??[]" multiple/></div>
<fieldset class="full task-assignee-fieldset">
    <legend><span>03</span><strong>Assign members</strong></legend>
    <p>Select one or more active chapter members. Each member can accept or decline the assignment.</p>
    <div class="task-assignee-options">
        @forelse($assignableMembers as $member)
            <label><input type="checkbox" name="assignees[]" value="{{ $member->id }}" @checked(in_array((string) $member->id, $selectedAssignees, true))><span><b>{{ $member->name }}</b><small>{{ ucfirst($member->role) }}</small></span></label>
        @empty
            <p class="hint">No active members are available for assignment.</p>
        @endforelse
    </div>
</fieldset>
@endif
@if($creating)
<input type="hidden" name="status" value="To Do">
@else
<div class="full task-form-section-heading"><span>{{ $manages ? '04' : '01' }}</span><div><h3>Progress and proof of work</h3><p>Update progress, explain blockers, and record evidence when the task is completed.</p></div></div>
<x-field name="status" label="Task status" type="select" :options="array_combine(['To Do','In Progress','Blocked','Completed'],['To Do','In Progress','Blocked','Completed'])" :value="$task->status??'To Do'" required/>
<x-field name="notes" label="Progress update or blocker" type="textarea" :value="$task->notes"/>
<x-field name="evidence" label="Completion evidence or project document reference" type="textarea" :value="$task->evidence"/>
@endif
<div class="full task-form-actions"><button class="btn secondary" type="button" data-task-dialog-close>Cancel</button><button class="btn primary"><x-icon name="{{ $task->exists ? 'save' : 'plus' }}" /> {{ $task->exists?'Save task changes':'Create task' }}</button></div></form>
