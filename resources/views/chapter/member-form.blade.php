@extends('layouts.chapter')
@section('title',$member->exists?'Manage member':'Register member')
@section('content')
<div class="page-heading"><div><div class="eyebrow">MEMBERS & ACCOUNTS</div><h1>{{ $member->exists?'Manage member':'Register member' }}</h1><p>Set chapter membership details, account access, and explicit review authority.</p></div></div>
<form method="POST" action="{{ $member->exists?route('members.update',$member):route('members.store') }}">@csrf @if($member->exists)@method('PUT')@endif<section class="panel form-panel"><div class="form-grid">
<x-field name="name" label="Full name" :value="$member->name" required/><x-field name="email" label="Email / sign-in name" type="email" :value="$member->email" required/>
<x-field name="member_no" label="Member number" :value="$member->member_no"/>
<x-field name="role" label="Permanent account role" type="select" :options="['admin'=>'Admin','bod'=>'Board of Directors','treasurer'=>'Treasurer','member'=>'General Member']" :value="$member->role??'member'" required/>
<x-field name="status" label="Account status" type="select" :options="['active'=>'Active','inactive'=>'Inactive']" :value="$member->status??'active'" required/>
<x-field name="profile[nickname]" label="Nickname" :value="$member->profile['nickname']??''"/>
<x-field name="profile[phone]" label="Contact number" :value="$member->profile['phone']??''"/>
<x-field name="profile[address]" label="Address" type="textarea" :value="$member->profile['address']??''"/>
<x-field name="profile[joined_on]" label="Membership date" type="date" :value="$member->profile['joined_on']??''"/>
<x-field name="profile[emergency_contact]" label="Emergency contact (optional)" :value="$member->profile['emergency_contact']??''"/>
<x-field name="password" :label="$member->exists?'New password (leave blank to keep current)':'Initial password'" type="password" :required="!$member->exists" minlength="10" autocomplete="new-password"/>
<x-field name="password_confirmation" label="Confirm password" type="password" :required="!$member->exists" autocomplete="new-password"/>
<div class="full"><h3>Review authority</h3><p class="hint">Only Admin and BOD accounts can receive these permissions. Project Chair is assigned separately within an approved project.</p><label class="check-field"><input type="checkbox" name="concept_reviewer" value="1" @checked(old('concept_reviewer',$member->concept_reviewer))> President / authorized concept reviewer</label><label class="check-field"><input type="checkbox" name="proposal_reviewer" value="1" @checked(old('proposal_reviewer',$member->proposal_reviewer))> Formal proposal approving authority</label></div>
<div class="full button-row"><a class="btn secondary" href="{{ route('members') }}">Cancel</a><button class="btn primary">Save member account</button></div></div></section></form>
@endsection
