@php $manages=$project->canManage(auth()->user()); @endphp
<form method="POST" action="{{ $task->exists?route('tasks.update',[$project,$task]):route('tasks.store',$project) }}" class="form-grid">@csrf @if($task->exists) @method('PUT') @endif
@if($manages)
<x-field name="title" label="Task title" :value="$task->title" required/>
<x-field name="priority" label="Priority" type="select" :options="array_combine(['Low','Medium','High','Urgent'],['Low','Medium','High','Urgent'])" :value="$task->priority??'Medium'" required/>
<x-field name="description" label="Task description" type="textarea" :value="$task->description"/>
<x-field name="assignees" label="Assigned members (select one or more)" type="select" :options="$members->pluck('name','id')->all()" :value="$task->assignees??[]" multiple required/>
<x-field name="starts_on" label="Start date" type="date" :value="$task->starts_on?->format('Y-m-d')"/>
<x-field name="deadline" label="Deadline" type="date" :value="$task->deadline?->format('Y-m-d')" required/>
<x-field name="milestone" label="Milestone" :value="$task->milestone"/>
<x-field name="dependencies" label="Prerequisite tasks (optional)" type="select" :options="$project->tasks->where('id','!=',$task->id)->pluck('title','id')->all()" :value="$task->dependencies??[]" multiple/>
@endif
<x-field name="status" label="Task status" type="select" :options="array_combine(['To Do','In Progress','Blocked','Completed'],['To Do','In Progress','Blocked','Completed'])" :value="$task->status??'To Do'" required/>
<x-field name="notes" label="Progress notes" type="textarea" :value="$task->notes"/>
<x-field name="evidence" label="Completion evidence / document references" type="textarea" :value="$task->evidence"/>
<div class="full"><button class="btn primary">{{ $task->exists?'Save task changes':'Create task' }}</button></div></form>
