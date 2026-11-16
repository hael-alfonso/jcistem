@extends('layouts.chapter')
@section('title', 'Partnership Agreements')
@section('content')
@php($notificationTargets = \App\Support\NotificationIndicators::for(auth()->user()))
<div class="page-heading"><div><div class="eyebrow">PARTNERSHIPS</div><h1>Partnership Agreements</h1><p>Track draft terms, Admin review, approval for printing, and signed copies.</p></div><a class="btn secondary" href="{{ route('partners.index') }}">Partner Directory</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Agreement</th><th>Partner</th><th>Project</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($agreements as $agreement)@php($noticeId = $notificationTargets['review_agreements'][$agreement->id] ?? null)<tr><td><strong>{{ $agreement->title }}</strong><small>{{ $agreement->created_at?->format('M d, Y') }}</small>@if($noticeId)@include('chapter.partials.unread-indicator', ['notificationId' => $noticeId, 'label' => 'Needs your review'])@endif</td><td>{{ $agreement->partner?->name }}</td><td>{{ $agreement->project?->title }}</td><td><span class="badge">{{ $agreement->status }}</span></td><td>@if($noticeId)<form method="POST" action="{{ route('notifications.open', $noticeId) }}">@csrf<button class="text-link" type="submit">View</button></form>@else<a class="text-link" href="{{ route('agreements.show', $agreement) }}">View</a>@endif</td></tr>
@empty<tr><td colspan="5"><div class="empty-state compact"><h3>No partnership agreements yet</h3><p>Open a partner record to prepare one for an approved project.</p></div></td></tr>@endforelse
</tbody></table></div>@include('chapter.partials.pagination',['items'=>$agreements])</section>
@endsection
