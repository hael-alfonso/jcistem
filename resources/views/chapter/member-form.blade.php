@extends('layouts.chapter')
@section('title',$member->exists?'Manage member':'Register member')
@section('content')
<div class="page-heading"><div><div class="eyebrow">MEMBERS & ACCOUNTS</div><h1>{{ $member->exists?'Manage member':'Register member' }}</h1><p>Manage chapter membership details. Account access is controlled by Admin.</p></div></div>
<form method="POST" action="{{ $member->exists?route('members.update',$member):route('members.store') }}">@csrf @if($member->exists)@method('PUT')@endif<section class="panel form-panel"><div class="form-grid">
<x-field name="name" label="Full name" :value="$member->name" required/>
@if(auth()->user()->role === 'admin')<x-field name="username" label="Username for sign-in" :value="$member->username" required autocomplete="username"/><x-field name="email" label="Email address" type="email" :value="$member->email" required autocomplete="email"/>
@endif
<x-field name="member_no" label="Member number" :value="$member->member_no"/>
@if(auth()->user()->role === 'admin')
<x-field name="role" label="Permanent account role" type="select" :options="['admin'=>'Admin','bod'=>'Board of Directors','treasurer'=>'Treasurer','member'=>'General Member']" :value="$member->role??'member'" required/>
<x-field name="status" label="Account status" type="select" :options="['active'=>'Active','inactive'=>'Inactive']" :value="$member->status??'active'" required/>
@endif
<x-field name="profile[nickname]" label="Nickname" :value="$member->profile['nickname']??''"/>
<x-field name="profile[phone]" label="Contact number" :value="$member->profile['phone']??''"/>
<x-field name="profile[address]" label="Address" type="textarea" :value="$member->profile['address']??''"/>
@if(!empty($member->profile['occupation']) || !empty($member->profile['organization']) || !empty($member->profile['interests']))<div class="full"><h3>Membership application details</h3><p><strong>Current status:</strong> {{ $member->profile['occupation'] ?? 'Not provided' }} · <strong>School or organization:</strong> {{ $member->profile['organization'] ?? 'Not provided' }}</p><p><strong>Areas of interest:</strong> {{ implode(', ', $member->profile['interests'] ?? []) ?: 'Not provided' }}</p></div>@endif
<x-field name="profile[joined_on]" label="Membership date" type="date" :value="$member->profile['joined_on']??''"/>
<x-field name="profile[emergency_contact]" label="Emergency contact (optional)" :value="$member->profile['emergency_contact']??''"/>
@if(auth()->user()->role === 'admin')
<x-field name="password" :label="$member->exists?'New password (leave blank to keep current)':'Initial password'" type="password" :required="!$member->exists" minlength="10" autocomplete="new-password"/>
<x-field name="password_confirmation" label="Confirm password" type="password" :required="!$member->exists" autocomplete="new-password"/>
<div class="full"><p class="hint">Admin reviews concepts and completion reports. Treasurer reviews feasibility and liquidation. Project chair is assigned only after a recorded BOD approval.</p></div>
@endif
<div class="full button-row"><a class="btn secondary" href="{{ route('members') }}"><x-icon name="close" /> Cancel</a><button class="btn primary"><x-icon name="save" /> Save member details</button></div></div></section></form>
@endsection
