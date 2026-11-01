@php
    $workProgress = \App\Support\ProjectProgress::summarize($project, (int) $completedTasks, (int) $totalTasks);
@endphp
<div class="project-work-progress" aria-label="Project progress and schedule">
    <div class="project-work-progress-heading"><strong>Project progress</strong><span>{{ $workProgress['percent'] }}%</span></div>
    <div class="project-work-progress-track">
        <progress value="{{ $workProgress['percent'] }}" max="100" aria-label="{{ $project->title }}: {{ $workProgress['percent'] }} percent overall project progress"></progress>
        @if($workProgress['elapsed'] !== null && $workProgress['elapsed'] > 0)
            <span class="project-work-progress-marker" style="left: {{ $workProgress['elapsed'] }}%" aria-hidden="true"></span>
        @endif
    </div>
    <div class="project-work-progress-caption"><span class="project-pace {{ $workProgress['pace'] === 'Behind pace' || $workProgress['pace'] === 'Past target date' ? 'is-behind' : '' }}">{{ $workProgress['pace'] }}</span><span>@if($workProgress['elapsed'] !== null)Time marker: {{ $workProgress['elapsed'] }}% elapsed · Due {{ $workProgress['due'] }}@elseif($workProgress['due'])Target {{ $workProgress['due'] }}@endif</span></div>
</div>
