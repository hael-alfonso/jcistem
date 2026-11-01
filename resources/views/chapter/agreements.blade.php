@extends('layouts.chapter')
@section('title', 'Partnership Agreements')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PARTNERSHIPS</div><h1>Partnership Agreements</h1><p>Track draft terms, Admin review, approval for printing, and signed copies.</p></div><a class="btn secondary" href="{{ route('partners.index') }}">Partner Directory</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Agreement</th><th>Partner</th><th>Project</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($agreements as $agreement)<tr><td><strong>{{ $agreement->title }}</strong><small>{{ $agreement->created_at?->format('M d, Y') }}</small></td><td>{{ $agreement->partner?->name }}</td><td>{{ $agreement->project?->title }}</td><td><span class="badge">{{ $agreement->status }}</span></td><td><a class="text-link" href="{{ route('agreements.show', $agreement) }}">View</a></td></tr>
@empty<tr><td colspan="5"><div class="empty-state compact"><h3>No partnership agreements yet</h3><p>Open a partner record to prepare one for an approved project.</p></div></td></tr>@endforelse
</tbody></table></div>@include('chapter.partials.pagination',['items'=>$agreements])</section>
@endsection
