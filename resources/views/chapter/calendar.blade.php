@extends('layouts.chapter')
@section('title', 'Calendar')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PLAN THE CHAPTER'S WORK</div><h1>Calendar</h1><p>Projects, meetings, task deadlines, and member dues in one month view.</p></div></div>
<div class="calendar-shell">
    <section class="calendar-board" aria-label="{{ $month->format('F Y') }} calendar">
        <header class="calendar-toolbar">
            <div><span class="eyebrow">CHAPTER SCHEDULE</span><h2>{{ $month->format('F Y') }}</h2></div>
            <div class="calendar-controls">
                <a class="calendar-today" href="{{ route('calendar') }}">Today</a>
                <a class="calendar-step" href="{{ route('calendar', ['month' => $month->subMonth()->format('Y-m')]) }}" aria-label="Previous month">&lsaquo;</a>
                <a class="calendar-step" href="{{ route('calendar', ['month' => $month->addMonth()->format('Y-m')]) }}" aria-label="Next month">&rsaquo;</a>
                <form method="GET" action="{{ route('calendar') }}" class="calendar-jump"><label class="sr-only" for="calendar-month">Jump to month</label><input id="calendar-month" type="month" name="month" value="{{ $month->format('Y-m') }}"><button type="submit">Go</button></form>
            </div>
        </header>
        <div class="calendar-scroll">
            <div class="calendar-weekdays" aria-hidden="true">@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $weekday)<span>{{ $weekday }}</span>@endforeach</div>
            <div class="calendar-month">
                @foreach($weeks as $week)
                    @foreach($week as $day)
                        @php $date = $day->toDateString(); $dayItems = $calendarItems[$date] ?? []; @endphp
                        <div class="calendar-cell {{ $day->month !== $month->month ? 'is-outside' : '' }} {{ $date === $selectedDay ? 'is-selected' : '' }} {{ $date === now()->toDateString() ? 'is-today' : '' }}">
                            <a class="calendar-date" href="{{ route('calendar', ['month' => $day->format('Y-m'), 'day' => $date]) }}" aria-label="Show {{ $day->format('F j, Y') }} agenda">{{ $day->day }}</a>
                            <div class="calendar-cell-events">
                                @foreach(array_slice($dayItems, 0, 3) as $item)
                                    @if($item['url'])<a class="calendar-event kind-{{ \Illuminate\Support\Str::slug($item['type']) }}" href="{{ $item['url'] }}" title="{{ $item['context'] }}">{{ $item['title'] }}</a>
                                    @else<span class="calendar-event kind-{{ \Illuminate\Support\Str::slug($item['type']) }}" title="{{ $item['context'] }}">{{ $item['title'] }}</span>@endif
                                @endforeach
                                @if(count($dayItems) > 3)<a class="calendar-more" href="{{ route('calendar', ['month' => $day->format('Y-m'), 'day' => $date]) }}">+{{ count($dayItems) - 3 }} more</a>@endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
        <div class="calendar-legend"><span><i class="legend-project"></i>Projects</span><span><i class="legend-activity"></i>Activities</span><span><i class="legend-task"></i>Tasks</span><span><i class="legend-dues"></i>Dues</span></div>
    </section>
    <aside class="calendar-day-panel" aria-label="Selected day agenda">
        <span class="eyebrow">SELECTED DAY</span><h2>{{ \Carbon\Carbon::parse($selectedDay)->format('l, F j') }}</h2><p class="calendar-day-year">{{ \Carbon\Carbon::parse($selectedDay)->format('Y') }}</p>
        @forelse($calendarItems[$selectedDay] ?? [] as $item)
            <div class="calendar-agenda-item"><span class="calendar-agenda-kind kind-{{ \Illuminate\Support\Str::slug($item['type']) }}">{{ $item['type'] }}</span>
                @if($item['url'])<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@else<strong>{{ $item['title'] }}</strong>@endif
                @if($item['context'])<small>{{ $item['context'] }}</small>@endif
            </div>
        @empty<div class="calendar-day-empty"><x-icon name="calendar"/><strong>Nothing scheduled</strong><span>Choose another day to see its agenda.</span></div>@endforelse
    </aside>
</div>
@if(auth()->user()->role === 'admin')
<details class="panel form-panel calendar-create no-print"><summary class="large-summary">Schedule a chapter activity</summary>
    <form method="POST" action="{{ route('calendar.store') }}" class="form-grid">@csrf
        <x-field name="title" label="Activity title" required/>
        <x-field name="type" label="Activity type" type="select" :options="array_combine(['Activity','Meeting','Review'],['Activity','Meeting','Review'])" required/>
        <x-field name="starts_on" label="Start date" type="date" :value="$selectedDay" required/>
        <x-field name="ends_on" label="End date" type="date"/>
        <x-field name="venue" label="Venue"/>
        <x-field name="description" label="Details" type="textarea"/>
        <div class="full"><button class="btn primary" type="submit">Schedule activity</button></div>
    </form>
</details>
@endif
@endsection
