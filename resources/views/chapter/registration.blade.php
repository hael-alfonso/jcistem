@extends('layouts.chapter')
@section('title', 'Review registration')
@section('content')
<div class="page-heading"><div><div class="eyebrow">REGISTRATION</div><h1>{{ $registration->name }}</h1><p>{{ $registration->reference }} · Submitted {{ $registration->created_at->format('d M Y, g:i A') }}</p></div><a class="btn secondary" href="{{ route('registrations') }}"><x-icon name="arrowLeft" /> Back to registrations</a></div>
<section class="panel form-panel"><h2>Membership application</h2><div class="form-grid"><div><strong>Username</strong><p>{{ $registration->username }}</p></div><div><strong>Email address</strong><p>{{ $registration->email }}</p></div><div><strong>Mobile number</strong><p>{{ $registration->phone ?: 'Not provided' }}</p></div><div><strong>City / address</strong><p>{{ $registration->application_details['address'] ?? 'Not provided' }}</p></div><div><strong>Current status</strong><p>{{ $registration->application_details['occupation'] ?? 'Not provided' }}</p></div><div><strong>School, employer, or business</strong><p>{{ $registration->application_details['organization'] ?? 'Not provided' }}</p></div><div><strong>Areas of interest</strong><p>{{ implode(', ', $registration->application_details['interests'] ?? []) ?: 'Not provided' }}</p></div><div><strong>Application status</strong><p>{{ ucfirst($registration->status) }}</p></div><div class="full"><strong>Reason for joining</strong><p class="preserve-lines">{{ $registration->reason }}</p></div><div class="full"><strong>Consent recorded</strong><p>{{ $registration->consent_at?->format('d M Y, g:i A') ?? 'Not recorded' }}</p></div></div></section>
@if($registration->status === 'pending')
<form method="POST" action="{{ route('registrations.review', $registration) }}" class="panel form-panel registration-review">
    @csrf
    <h2>Admin decision</h2>
    <p>Approval admits the applicant to the member portal and creates an active <strong>General Member</strong> account. Confirm the chapter's membership decision, identity, and email ownership first.</p>
    <label class="registration-review-label" for="review_notes">Review notes (required for rejection)</label>
    <textarea id="review_notes" name="review_notes" maxlength="2000">{{ old('review_notes') }}</textarea>
    <label class="registration-verify"><input type="checkbox" name="verified_identity" value="1" @checked(old('verified_identity'))><span>I verified the chapter's decision on this application, the applicant's identity, and email ownership.</span></label>
    <div class="button-row"><button class="btn primary" name="decision" value="approve" type="submit"><x-icon name="check" /> Approve application</button><button class="btn secondary" name="decision" value="reject" type="submit"><x-icon name="close" /> Reject application</button></div>
</form>
@else
<section class="panel form-panel">
    <h2>Decision recorded</h2>
    <p>Reviewed by {{ $registration->reviewer?->name ?? 'Former administrator' }} on {{ $registration->reviewed_at?->format('d M Y, g:i A') }}.</p>
    @if($registration->review_notes)<p class="registration-reason">{{ $registration->review_notes }}</p>@endif
    @if($registration->user_id)<a class="text-link" href="{{ route('members.edit', $registration->user_id) }}">Open member account <x-icon name="arrow" /></a>@endif
</section>
@endif
@endsection
