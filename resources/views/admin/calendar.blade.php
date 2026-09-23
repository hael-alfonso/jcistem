@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Calendar</h1>
        <p>Project schedules, milestones, deadlines, review dates and chapter activities.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" type="button" id="addEvent"><x-icon name="plus" /> Add activity</button>
    </div>
</div>

<div class="calendar-toolbar">
    <div class="month-title">September 2026</div>
    <div class="calendar-filters">
        <span class="filter-pill active">All</span>
        <span class="filter-pill">Project</span>
        <span class="filter-pill">Meeting</span>
        <span class="filter-pill">Review</span>
    </div>
</div>

<div class="calendar-grid">
    @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd)
        <div class="weekday">{{ $wd }}</div>
    @endforeach
    @foreach ($cells as $cell)
        @if (!$cell)
            <div class="day muted-day"></div>
        @else
            <div class="day {{ $cell['day'] === 23 ? 'today' : '' }}">
                <b>{{ $cell['day'] }}</b>
                @foreach ($cell['events'] as $e)
                    <a href="{{ $e['project'] ? route('admin.projects.show', $e['project']) : route('admin.calendar') }}" class="cal-item">{{ $e['title'] }}</a>
                @endforeach
            </div>
        @endif
    @endforeach
</div>

<div class="grid-2">
    <x-card title="Upcoming schedule" subtitle="Next project and review dates">
        <div class="events-list">
            @foreach ($events as $e)
                <a class="event-row" href="{{ $e['project'] ? route('admin.projects.show', $e['project']) : route('admin.calendar') }}">
                    <div class="date-tile">
                        <b>{{ date('j', strtotime($e['date'])) }}</b>
                        <span>{{ strtoupper(date('M', strtotime($e['date']))) }}</span>
                    </div>
                    <div>
                        <strong>{{ $e['title'] }}</strong>
                        <span>{{ $e['time'] }} • {{ $e['notes'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </x-card>
    <x-card title="Schedule insights" subtitle="Decision-useful summary">
        <div class="insight-list">
            <div><b>{{ count($events) }}</b><span>planned activities</span></div>
            <div><b>{{ collect($events)->where('type', 'Project')->count() }}</b><span>project dates</span></div>
            <div><b>{{ collect($events)->where('type', 'Review')->count() }}</b><span>review dates</span></div>
        </div>
    </x-card>
</div>
@endsection
