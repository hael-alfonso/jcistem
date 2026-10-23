@php $manages=$project->canManage(auth()->user()); @endphp
<form method="POST" action="{{ $task->exists?route('tasks.update',[$project,$task]):route('tasks.store',$project) }}" class="form-grid">@csrf @if($task->exists) @method('PUT') @endif
@if($manages)
<div class="full"><h3>Assignment and expected output</h3><p class="hint">Give members a clear deliverable and deadline. Link the task to a project milestone when applicable.</p></div>
<x-field name="title" label="Task title" :value="$task->title" required/>
<x-field name="priority" label="Priority" type="select" :options="array_combine(['Low','Medium','High','Urgent'],['Low','Medium','High','Urgent'])" :value="$task->priority??'Medium'" required/>
<x-field name="description" label="Instructions and expected deliverable" type="textarea" :value="$task->description"/>
<x-field name="assignees" label="Assigned members (select one or more)" type="select" :options="$assignableMembers->pluck('name','id')->all()" :value="$task->assignees??[]" multiple required/>
<x-field name="starts_on" label="Start date" type="date" :value="$task->starts_on?->format('Y-m-d')"/>
<x-field name="deadline" label="Deadline" type="date" :value="$task->deadline?->format('Y-m-d')" required/>
<x-field name="milestone" label="Milestone" :value="$task->milestone"/>
<x-field name="dependencies" label="Prerequisite tasks (optional)" type="select" :options="$project->tasks->where('id','!=',$task->id)->pluck('title','id')->all()" :value="$task->dependencies??[]" multiple/>
@endif
<div class="full"><h3>Progress and proof of work</h3><p class="hint">Assignees can update progress, explain blockers, and point to files or records that confirm completion.</p></div>
<x-field name="status" label="Task status" type="select" :options="array_combine(['To Do','In Progress','Blocked','Completed'],['To Do','In Progress','Blocked','Completed'])" :value="$task->status??'To Do'" required/>
<x-field name="notes" label="Progress update or blocker" type="textarea" :value="$task->notes"/>
<x-field name="evidence" label="Completion evidence or project document reference" type="textarea" :value="$task->evidence"/>
<div class="full"><button class="btn primary"><x-icon name="{{ $task->exists ? 'save' : 'plus' }}" /> {{ $task->exists?'Save task changes':'Create task' }}</button></div></form>
