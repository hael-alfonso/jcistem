@extends('layouts.chapter')
@section('title', 'Public inquiries')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PUBLIC CONTACT INBOX</div><h1>Public inquiries</h1><p>Review messages submitted through the JCI Carmona contact form.</p></div><a class="btn secondary" href="{{ route('contact') }}" target="_blank" rel="noopener noreferrer"><x-icon name="link" /> Open public form</a></div>
<div class="stats-grid three">
    <div class="stat-card"><span>New inquiries</span><strong>{{ $newCount }}</strong><small>Waiting for first review</small><x-icon name="info" /></div>
    <div class="stat-card"><span>In progress</span><strong>{{ \App\Models\ContactInquiry::where('status', 'in_progress')->count() }}</strong><small>Assigned or being handled</small><x-icon name="clock" /></div>
    <div class="stat-card"><span>Resolved</span><strong>{{ \App\Models\ContactInquiry::where('status', 'resolved')->count() }}</strong><small>Closed with a recorded outcome</small><x-icon name="check" /></div>
</div>
<form class="list-filters" method="GET" action="{{ route('inquiries') }}">
    <label><span>Search</span><input name="q" value="{{ request('q') }}" placeholder="Reference, sender, email, or subject"></label>
    <label><span>Status</span><select name="status"><option value="">All statuses</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Topic</span><select name="category"><option value="">All topics</option>@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach</select></label>
    <button class="btn primary" type="submit"><x-icon name="filter" /> Apply filters</button>
    @if(request()->filled('q') || request()->filled('status') || request()->filled('category'))<a class="text-link" href="{{ route('inquiries') }}"><x-icon name="close" /> Clear</a>@endif
</form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Inquiry</th><th>Sender</th><th>Topic</th><th>Received</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($inquiries as $inquiry)<tr><td><strong>{{ $inquiry->subject }}</strong><small>{{ $inquiry->reference }}</small></td><td>{{ $inquiry->name }}<small>{{ $inquiry->email }}</small></td><td>{{ $categories[$inquiry->category] ?? ucfirst($inquiry->category) }}</td><td>{{ $inquiry->created_at->format('d M Y') }}<small>{{ $inquiry->created_at->format('g:i A') }}</small></td><td><span class="badge">{{ $statuses[$inquiry->status] ?? ucfirst($inquiry->status) }}</span>@if($inquiry->assignee)<small>{{ $inquiry->assignee->name }}</small>@endif</td><td><a class="text-link" href="{{ route('inquiries.show', $inquiry) }}">Review <x-icon name="arrow" /></a></td></tr>
@empty<tr><td colspan="6"><div class="empty-state"><span class="empty-icon"><x-icon name="info" /></span><h3>No matching inquiries.</h3><p>New contact-form submissions will appear here.</p><a class="btn secondary" href="{{ route('inquiries') }}">Show all inquiries</a></div></td></tr>@endforelse
</tbody></table></div>@include('chapter.partials.pagination', ['items' => $inquiries])</section>
@endsection
