@extends('layouts.chapter')
@section('title','Member directory')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PEOPLE BEHIND THE IMPACT</div><h1>Member directory</h1><p>Find chapter members and see who can help with your work.</p></div>@if(auth()->user()->role==='admin')<a class="btn primary" href="{{ route('members.create') }}">+ Register member</a>@endif</div>
<form class="list-filters" method="GET" action="{{ route('members') }}">
    <label><span>Find a member</span><input name="q" value="{{ request('q') }}" placeholder="Name or member number"></label>
    <label><span>Role</span><select name="role"><option value="">All roles</option>@foreach(['admin'=>'Admin','bod'=>'BOD','treasurer'=>'Treasurer','member'=>'General member'] as $value=>$label)<option value="{{ $value }}" @selected(request('role')===$value)>{{ $label }}</option>@endforeach</select></label>
    <button class="btn primary" type="submit">Apply filters</button>
    @if(request()->filled('q') || request()->filled('role'))<a class="text-link" href="{{ route('members') }}">Clear</a>@endif
</form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Member</th><th>Member number</th><th>Role</th><th>Status</th>@if(auth()->user()->role==='admin')<th>Review authority</th><th></th>@endif</tr></thead><tbody>@forelse($members as $member)<tr><td><strong>{{ $member->name }}</strong>@if(auth()->user()->role==='admin')<small>{{ $member->email }}</small>@endif</td><td>{{ $member->member_no??'-' }}</td><td>{{ ['admin'=>'Admin','bod'=>'BOD','treasurer'=>'Treasurer','member'=>'General Member'][$member->role]??$member->role }}</td><td><span class="badge">{{ ucfirst($member->status) }}</span></td>@if(auth()->user()->role==='admin')<td>{{ $member->concept_reviewer?'Concept review ':'' }}{{ $member->proposal_reviewer?'Formal approval':'' }}</td><td><a class="text-link" href="{{ route('members.edit',$member) }}">Manage &rarr;</a></td>@endif</tr>@empty<tr><td colspan="6"><div class="empty-state"><h3>No matching members.</h3><p>Try a different name, number, or role.</p><a class="btn secondary" href="{{ route('members') }}">Show all members</a></div></td></tr>@endforelse</tbody></table></div>@include('chapter.partials.pagination',['items'=>$members])</section>
@endsection
