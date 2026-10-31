@php
    $workProgress = \App\Support\ProjectProgress::summarize($project, (int) $completedTasks, (int) $totalTasks);
@endphp
<div class="project-work-progress" aria-label="Project task progress and schedule">
    <div class="project-work-progress-heading"><strong>Task progress</strong><span>{{ $completedTasks }} of {{ $totalTasks }} · {{ $workProgress['percent'] }}%</span></div>
    <div class="project-work-progress-track">
        <progress value="{{ $workProgress['percent'] }}" max="100" aria-label="{{ $project->title }}: {{ $completedTasks }} of {{ $totalTasks }} tasks complete"></progress>
        @if($workProgress['elapsed'] !== null && $workProgress['elapsed'] > 0)
            <span class="project-work-progress-marker" style="left: {{ $workProgress['elapsed'] }}%" aria-hidden="true"></span>
        @endif
    </div>
    <div class="project-work-progress-caption"><span class="project-pace {{ $workProgress['pace'] === 'Behind pace' || $workProgress['pace'] === 'Past target date' ? 'is-behind' : '' }}">{{ $workProgress['pace'] }}</span><span>@if($workProgress['elapsed'] !== null)Time marker: {{ $workProgress['elapsed'] }}% elapsed · Due {{ $workProgress['due'] }}@elseif($workProgress['due'])Target {{ $workProgress['due'] }}@endif</span></div>
</div>
