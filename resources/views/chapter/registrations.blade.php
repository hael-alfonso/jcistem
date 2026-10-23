@extends('layouts.chapter')
@section('title', 'Registration requests')
@section('content')
<div class="page-heading"><div><div class="eyebrow">MEMBERS &amp; ACCOUNTS</div><h1>Registration requests</h1><p>Review public requests before granting a general member account.</p></div><a class="btn secondary" href="{{ route('register') }}" target="_blank" rel="noopener noreferrer"><x-icon name="link" /> Open registration form</a></div>
<div class="stats-grid three"><div class="stat-card"><span>Awaiting review</span><strong>{{ $pendingCount }}</strong><small>Admin decision required</small><x-icon name="userPlus" /></div></div>
<form class="list-filters" method="GET" action="{{ route('registrations') }}"><label><span>Status</span><select name="status"><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Approved</option><option value="rejected" @selected($status === 'rejected')>Rejected</option></select></label><button class="btn primary" type="submit"><x-icon name="filter" /> Apply filter</button></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Applicant</th><th>Reference</th><th>Submitted</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($requests as $registration)<tr><td><strong>{{ $registration->name }}</strong><small>{{ $registration->email }}</small></td><td>{{ $registration->reference }}</td><td>{{ $registration->created_at->format('d M Y') }}</td><td><span class="badge">{{ ucfirst($registration->status) }}</span></td><td><a class="text-link" href="{{ route('registrations.show', $registration) }}">Review <x-icon name="arrow" /></a></td></tr>
@empty<tr><td colspan="5"><div class="empty-state"><span class="empty-icon"><x-icon name="userPlus" /></span><h3>No {{ $status }} requests.</h3><p>Registration requests will appear here.</p></div></td></tr>@endforelse
</tbody></table></div>@include('chapter.partials.pagination', ['items' => $requests])</section>
@endsection
