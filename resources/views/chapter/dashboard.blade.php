@extends('layouts.chapter')
@section('title', 'Dashboard')
@section('content')
@php
    $role = auth()->user()->role;
    $active = $dashboardProjects->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review']);
    $overdue = $tasks->filter(fn ($task) => $task->deadline?->lt(today()))->count();
    $balance = $financeCharts['allocated'] - $financeCharts['spent'];
    $overBudget = $financeCharts['rows']->filter(fn ($row) => $row['spent'] > $row['allocated'])->count();
    $hour = now()->hour;
    $greeting = match (true) {
        $hour < 12 => 'Good morning',
        $hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
    $firstName = preg_split('/\s+/', trim(auth()->user()->name))[0] ?: 'there';
@endphp
<div class="dashboard-page">
<div class="page-heading dashboard-heading">
    <div class="dashboard-heading-copy"><div class="eyebrow">{{ \App\Support\WorkspaceNav::label($role) }} workspace</div><h1>{{ $greeting }}, {{ $firstName }}.</h1><p>{{ match($role) { 'member' => 'Your next assignment, project work, and dues in one place.', 'treasurer' => 'Review claims, allocate approved project funds, and keep the ledger accurate.', 'bod' => 'Follow the Board agenda and the progress of chapter projects.', default => 'Pending reviews, chapter projects, and funding at a glance.' } }}</p></div>
    @if($role === 'treasurer')<a class="btn primary" href="{{ route('expense-claims.index', ['status' => 'pending']) }}"><x-icon name="receipt"/> Review claims</a>
    @elseif(in_array($role, ['admin', 'bod']))<a class="btn primary" href="#dashboard-reviews"><x-icon name="fileSignature"/> Review requests</a>
    @else<a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> Create Project</a>@endif
</div>
<div class="stats-grid" aria-label="Dashboard summary">
    @if($role === 'treasurer')
        <x-dashboard-stat label="Allocated funds" :value="'PHP '.number_format($financeCharts['allocated'], 2)" note="Approved project allocations · all time" icon="wallet" tone="budget" :money="true" :href="route('finance')"/>
        <x-dashboard-stat label="Posted expenses" :value="'PHP '.number_format($financeCharts['spent'], 2)" note="Approved projects · posted debits" icon="receipt" tone="expenses" :money="true" :href="route('ledger')"/>
        <x-dashboard-stat label="Chapter funds available" :value="'PHP '.number_format((float) $chapterFunds, 2)" note="Posted income minus posted expenses" icon="wallet" :tone="(float) $chapterFunds < 0 ? 'expenses' : 'balance'" :money="true" :href="route('finance')"/>
        <x-dashboard-stat label="Projects over budget" :value="$overBudget" note="Posted expenses exceed allocations" icon="info" tone="reviews" :href="route('finance')"/>
    @else
        <x-dashboard-stat :label="$role === 'member' ? 'My active projects' : 'Active projects'" :value="$active->count()" note="Approved, ongoing, or in completion review" icon="briefcase" tone="projects" :href="route('projects', $role === 'member' ? ['filter' => 'mine'] : [])"/>
        @if($role === 'member')
            <x-dashboard-stat label="My dues balance" :value="'PHP '.number_format($myDuesBalance, 2)" note="Outstanding after posted payments" icon="receipt" tone="expenses" :money="true" :href="route('dues')"/>
        @else
            <x-dashboard-stat :label="$role === 'bod' ? 'Board agenda' : 'Awaiting review'" :value="$pending->count()" :note="$role === 'bod' ? 'Projects awaiting Board deliberation' : 'Requests matching your review role'" icon="fileSignature" tone="reviews" href="#dashboard-reviews"/>
        @endif
        @if($role === 'admin')
            <x-dashboard-stat label="Completed projects" :value="$dashboardProjects->whereIn('status', ['Completed', 'Archived'])->count()" note="Completed and archived projects" icon="check" tone="balance" :href="route('projects.completed')"/>
            <x-dashboard-stat label="Chapter funds available" :value="'PHP '.number_format((float) $chapterFunds, 2)" note="Posted income minus posted expenses" icon="wallet" :tone="(float) $chapterFunds < 0 ? 'expenses' : 'balance'" :money="true" :href="route('finance')"/>
        @else
            <x-dashboard-stat label="Chapter funds available" :value="'PHP '.number_format((float) $chapterFunds, 2)" note="Posted income minus posted expenses" icon="wallet" :tone="(float) $chapterFunds < 0 ? 'expenses' : 'balance'" :money="true" :href="route('finance')"/>
            <x-dashboard-stat label="My open tasks" :value="$tasks->count()" :note="$overdue.' overdue · excludes completed tasks'" icon="checklist" :tone="$overdue ? 'reviews' : 'tasks'" :href="route('tasks', ['mine' => 1])"/>
        @endif
    @endif
</div>
@if($role === 'treasurer')
    <section class="panel dashboard-action-panel" aria-labelledby="treasurer-attention-title"><div class="panel-heading"><div><h2 id="treasurer-attention-title">Needs your attention</h2><p>Claims, approved plans, and fund handovers waiting for a finance action</p></div><a class="text-link" href="{{ route('ledger') }}">Open ledger</a></div><div class="dashboard-action-grid"><a href="{{ route('expense-claims.index', ['status' => 'pending']) }}"><strong>{{ $pendingClaims->count() }} pending {{ \Illuminate\Support\Str::plural('claim', $pendingClaims->count()) }}</strong><span>Verify receipts and decide whether each claim can be posted.</span></a><a href="{{ route('projects', ['filter' => 'approved']) }}"><strong>{{ $financeActionProjects->count() }} plans without allocation</strong><span>Set expense-line allocations before project spending starts.</span></a><a href="{{ route('projects', ['filter' => 'review']) }}"><strong>{{ $pending->count() }} finance {{ \Illuminate\Support\Str::plural('review', $pending->count()) }}</strong><span>Check budget feasibility on proposals awaiting your review.</span></a></div>
    @if($pendingClaims->isNotEmpty() || $pendingFundReleases->isNotEmpty())<div class="dashboard-action-list">@foreach($pendingFundReleases->take(2) as $release)<a class="attention-row" href="{{ route('projects.show', $release->project_id) }}#budget"><span class="project-mark"><x-icon name="wallet"/></span><span class="row-main"><strong>{{ $release->project?->title ?? 'Project fund release' }}</strong><small>Waiting for {{ $release->chair?->name ?? 'Project Chair' }} to confirm PHP {{ number_format((float) $release->amount, 2) }}</small></span><span class="arrow" aria-hidden="true">&rarr;</span></a>@endforeach @foreach($pendingClaims->take(3) as $claim)@php $claimProject = $projects->firstWhere('id', $claim->project_id); @endphp<a class="attention-row" href="{{ route('expense-claims.index', ['project' => $claim->project_id, 'status' => 'pending']) }}"><span class="project-mark"><x-icon name="receipt"/></span><span class="row-main"><strong>{{ $claimProject?->title ?? 'Project claim' }}</strong><small>{{ $claim->submitter?->name ?? 'Project Chair' }} &middot; PHP {{ number_format((float) $claim->amount, 2) }}</small></span><span class="arrow" aria-hidden="true">&rarr;</span></a>@endforeach</div>@endif</section>
@elseif($role === 'member')
    <section class="panel dashboard-action-panel" aria-labelledby="member-attention-title"><div class="panel-heading"><div><h2 id="member-attention-title">Your next steps</h2><p>Open the right project to accept, finish, confirm funds, or lead work</p></div><a class="text-link" href="{{ route('tasks', ['mine' => 1]) }}">My task board</a></div><div class="dashboard-action-grid"><a href="{{ route('tasks', ['mine' => 1]) }}"><strong>{{ $pendingAssignments->count() }} {{ \Illuminate\Support\Str::plural('assignment', $pendingAssignments->count()) }} to answer</strong><span>Accept or decline tasks assigned to you.</span></a><a href="{{ route('tasks', ['mine' => 1]) }}"><strong>{{ $readyTasks->count() }} ready to finish</strong><span>Add completion evidence when your work is done.</span></a><a href="{{ route('projects', ['filter' => 'chairing']) }}"><strong>{{ $chairProjects->count() }} {{ \Illuminate\Support\Str::plural('project', $chairProjects->count()) }} you chair</strong><span>Keep the plan, team, and delivery moving.</span></a></div>@if($pendingFundReleases->isNotEmpty())<div class="dashboard-action-list">@foreach($pendingFundReleases->take(3) as $release)<a class="attention-row" href="{{ route('projects.show', $release->project_id) }}#budget"><span class="project-mark"><x-icon name="wallet"/></span><span class="row-main"><strong>Confirm PHP {{ number_format((float) $release->amount, 2) }} received</strong><small>{{ $release->project?->title }} · sent by {{ $release->treasurer?->name ?? 'Treasurer' }}</small></span><span class="arrow" aria-hidden="true">&rarr;</span></a>@endforeach</div>@endif</section>
    @include('chapter.partials.dashboard-tasks')
@else
    @if($role === 'bod')<div class="dashboard-role-note"><x-icon name="fileSignature"/><span>Projects on the Board agenda are ready for deliberation. The Admin records the official decision after the meeting.</span></div>@endif
    @if($role === 'admin')<section class="panel dashboard-action-panel" aria-labelledby="admin-attention-title"><div class="panel-heading"><div><h2 id="admin-attention-title">Admin actions</h2><p>Requests that need an account or project decision</p></div></div><div class="dashboard-action-grid"><a href="{{ route('registrations') }}"><strong>{{ $adminActions['registrations'] }} membership {{ \Illuminate\Support\Str::plural('application', $adminActions['registrations']) }}</strong><span>Review new members and account access.</span></a><a href="{{ route('projects', ['filter' => 'review']) }}"><strong>{{ $pending->count() }} project {{ \Illuminate\Support\Str::plural('review', $pending->count()) }}</strong><span>Move concepts and formal proposals forward.</span></a><a href="{{ route('records', ['kind' => 'reports']) }}"><strong>{{ $adminActions['reports'] }} completion {{ \Illuminate\Support\Str::plural('report', $adminActions['reports']) }}</strong><span>Check outcomes and submitted evidence.</span></a></div></section>@endif
    @include('chapter.partials.dashboard-review')
    @if($role === 'bod' && $tasks->isNotEmpty())
        @include('chapter.partials.dashboard-task-list')
    @endif
@endif
@include('chapter.partials.dashboard-monitor')
<div class="dashboard-grid dashboard-recent-grid" aria-label="Recent activity">
    <section class="panel dashboard-project-list"><div class="panel-heading"><div><h2>Recent projects</h2><p>Latest updates in your project workspace</p></div><a class="text-link" href="{{ route('projects', $role === 'member' ? ['filter' => 'mine'] : []) }}">All projects</a></div>
        @forelse($recentProjects as $project)<a class="attention-row" href="{{ route('projects.show', $project) }}"><span class="project-mark"><x-icon name="briefcase"/></span><span class="row-main"><strong>{{ $project->title }}</strong><small>{{ \App\Support\ProjectFlow::statusLabel($project) }} · {{ $project->updated_at?->format('M d') }}</small></span><span class="arrow" aria-hidden="true">&rarr;</span></a>@empty<p class="panel-empty">No projects to show yet.</p>@endforelse
    </section>
    @if($role === 'bod')
    <section class="panel dashboard-project-list"><div class="panel-heading"><div><h2>Recent Board decisions</h2><p>Official decisions recorded for chapter projects</p></div><a class="text-link" href="{{ route('projects', ['filter' => 'board']) }}">Board projects</a></div>
        @forelse($recentDecisions as $decision)<a class="attention-row" href="{{ route('projects.show', $decision->project_id) }}#proposal"><span class="project-mark"><x-icon name="fileSignature"/></span><span class="row-main"><strong>{{ $decision->project?->title ?? 'Project' }}</strong><small>{{ ucfirst($decision->decision) }} &middot; {{ $decision->meeting_date?->format('M d, Y') ?? 'Date not recorded' }}</small></span><span class="arrow" aria-hidden="true">&rarr;</span></a>@empty<p class="panel-empty">Board decisions will appear here after they are recorded.</p>@endforelse
    </section>
    @else
    <section class="panel dashboard-project-list"><div class="panel-heading"><div><h2>Recent transactions</h2><p>Posted income and expenses for visible projects</p></div><a class="text-link" href="{{ route('finance') }}">Financial overview</a></div>
        @forelse($recentTransactions as $entry)<a class="attention-row" href="{{ route('projects.show', $entry->project) }}#budget"><span class="project-mark"><x-icon name="receipt"/></span><span class="row-main"><strong>{{ $entry->project?->title ?? 'Project' }}</strong><small>{{ $entry->description ?: $entry->category }} · {{ $entry->transaction_date?->format('M d') }}</small></span><strong class="dashboard-transaction-amount {{ $entry->direction === 'debit' ? 'is-expense' : 'is-income' }}">{{ $entry->direction === 'debit' ? '−' : '+' }}PHP {{ number_format((float) $entry->amount, 2) }}</strong></a>@empty<p class="panel-empty">No posted project transactions yet.</p>@endforelse
    </section>
    @endif
</div>
</div>
@endsection
