@extends('layouts.chapter')
@php
$memberPageTitle = request()->routeIs('accounts') ? 'User Accounts & Roles' : (request()->routeIs('members.manage') ? 'Manage Member Records' : 'Member directory');
@endphp
@section('title',$memberPageTitle)
@section('content')
<div class="page-heading"><div><div class="eyebrow">PEOPLE BEHIND THE IMPACT</div><h1>{{ $memberPageTitle }}</h1><p>Find chapter members and see who can help with your work.</p></div>@if(auth()->user()->role==='admin')<a class="btn primary" href="{{ route('members.create') }}"><x-icon name="userPlus" /> Register member</a>@endif</div>
<form class="list-filters" method="GET" action="{{ url()->current() }}">
    <label><span>Find a member</span><input name="q" value="{{ request('q') }}" placeholder="Name or member number"></label>
    <label><span>Role</span><select name="role"><option value="">All roles</option>@foreach(['admin'=>'Admin','bod'=>'BOD','treasurer'=>'Treasurer','member'=>'General member'] as $value=>$label)<option value="{{ $value }}" @selected(request('role')===$value)>{{ $label }}</option>@endforeach</select></label>
    <button class="btn primary" type="submit"><x-icon name="filter" /> Apply filters</button>
    @if(request()->filled('q') || request()->filled('role'))<a class="text-link" href="{{ url()->current() }}"><x-icon name="close" /> Clear</a>@endif
</form>
<section class="panel member-directory"><div class="table-wrap"><table><thead><tr><th>Member</th><th>JCI Carmona ID</th><th>Role</th><th>Status</th>@if(in_array(auth()->user()->role,['admin','bod']))<th>Review authority</th><th></th>@endif</tr></thead><tbody>
@forelse($members as $member)
<tr><td data-label="Member"><span class="member-identity"><span class="member-avatar" aria-hidden="true">@if(!empty($member->profile['photo_path']) && ($member->status === 'active' || in_array(auth()->user()->role, ['admin', 'bod'], true)))<img src="{{ route('members.photo', $member) }}?v={{ $member->updated_at?->timestamp }}" width="40" height="40" alt="" loading="lazy" decoding="async">@else{{ strtoupper(mb_substr($member->name, 0, 1)) }}@endif</span><span><strong>{{ $member->name }}</strong>@if(auth()->user()->role==='admin')<small>{{ $member->email }}</small>@endif</span></span></td>
<td data-label="JCI Carmona ID">{{ $member->member_no ?? 'Pending ID' }}</td><td data-label="Role">{{ ['admin'=>'Admin','bod'=>'BOD','treasurer'=>'Treasurer','member'=>'General Member'][$member->role] ?? $member->role }}</td><td data-label="Status"><span class="badge {{ $member->status === 'active' ? 'status-approved' : 'status-revision' }}">{{ ucfirst($member->status) }}</span></td>
@if(in_array(auth()->user()->role,['admin','bod']))<td data-label="Review authority">{{ $member->concept_reviewer ? 'Concept review ' : '' }}{{ $member->proposal_reviewer ? 'Formal approval' : '' }}{{ !$member->concept_reviewer && !$member->proposal_reviewer ? '—' : '' }}</td><td data-label="Action"><a class="text-link" href="{{ route('members.edit',$member) }}"><x-icon name="edit" /> Manage</a></td>@endif</tr>
@empty<tr><td colspan="6"><div class="empty-state"><x-icon name="users"/><h3>No matching members</h3><p>Try a different name, number, or role.</p><a class="btn secondary" href="{{ url()->current() }}">Show all members</a></div></td></tr>@endforelse
</tbody></table></div>@include('chapter.partials.pagination',['items'=>$members])</section>
@endsection
