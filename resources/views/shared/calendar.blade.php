@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">CALENDAR</div><h1>Calendar</h1><p>Project schedules, reviews, and chapter activities.</p></div></div>
<div class="calendar-toolbar"><div class="month-title">September 2026</div></div>
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
                    <span class="cal-item">{{ $e['title'] }}</span>
                @endforeach
            </div>
        @endif
    @endforeach
</div>
<x-card title="Upcoming" subtitle="Next scheduled activities">
    <div class="events-list">
        @foreach ($events as $e)
            <div class="event-row">
                <div class="date-tile">
                    <b>{{ date('j', strtotime($e['date'])) }}</b>
                    <span>{{ strtoupper(date('M', strtotime($e['date']))) }}</span>
                </div>
                <div>
                    <strong>{{ $e['title'] }}</strong>
                    <span>{{ $e['time'] }} • {{ $e['notes'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
</x-card>
@endsection
